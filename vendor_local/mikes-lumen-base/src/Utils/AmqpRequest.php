<?php

namespace MikesLumenBase\Utils;

use Bschmitt\Amqp\Request;
use PhpAmqpLib\Wire\AMQPTable;

class AmqpRequest extends Request
{
    const MAX_CONNECT_RETRY_COUNT = 3;
    const CONNECT_RETRY_INTERVAL_SECONDS = 30;

    public $workQueueName;
    public $retryQueueName;
    public $routingKey;
    public $retryExchange;

    /**
     * Connect to rabbitmq with trials.
     * When first starting a rabbitmq container, it takes some time to properly respond.
     * So it needs to wait for the rabbitmq container being responsible.
     *
     * @return void
     */
    private function safeConnect()
    {
        $retryConnectCount = 0;
        while (true) {
            try {
                $this->connect();
                \Log::error('[Connected to rabbitmq]');
                break;
            } catch (\Exception $e) {
                \Log::error('[Failed to connect to rabbitmq]');
                $retryConnectCount += 1;
                if ($retryConnectCount >= AmqpRequest::MAX_CONNECT_RETRY_COUNT) {
                    throw $e;
                }
                sleep(AmqpRequest::CONNECT_RETRY_INTERVAL_SECONDS);
            }
        }
    }

    /**
     * Setup work exchange/queue, retry exchange/queue for the consumer and publisher.
     * The retry queue is delcared with x-dead-letter-exchange property
     * which move expired messages in the queue to the specified exchange.
     *
     * @param  string
     * @param  string
     * @return void
     */
    public function setupChannel(string $consumer, string $publisher)
    {
        $this->safeConnect();

        $this->workQueueName = $consumer . '-' . $publisher . '-work-queue';
        $this->retryQueueName = $consumer . '-' . $publisher . '-retry-queue';
        $this->routingKey = $publisher . '.#';
        $this->retryRoutingKey = '_retry.' . $consumer . '.' . $this->routingKey;
        $this->retryExchange = $this->getProperty('exchange_type') . '.retry';

        // Declare work exchange
        $this->channel->exchange_declare(
            $this->getProperty('exchange'),
            $this->getProperty('exchange_type'),
            $this->getProperty('exchange_passive'),
            $this->getProperty('exchange_durable'),
            $this->getProperty('exchange_auto_delete'),
            $this->getProperty('exchange_internal'),
            $this->getProperty('exchange_nowait'),
            $this->getProperty('exchange_properties')
        );

        // Declare retry exchange
        $this->channel->exchange_declare(
            $this->retryExchange,
            $this->getProperty('exchange_type'),
            $this->getProperty('exchange_passive'),
            $this->getProperty('exchange_durable'),
            $this->getProperty('exchange_auto_delete'),
            $this->getProperty('exchange_internal'),
            $this->getProperty('exchange_nowait'),
            $this->getProperty('exchange_properties')
        );

        // Declare work queue
        $this->queueInfo = $this->channel->queue_declare(
            $this->workQueueName,
            $this->getProperty('queue_passive'),
            $this->getProperty('queue_durable'),
            $this->getProperty('queue_exclusive'),
            $this->getProperty('queue_auto_delete'),
            $this->getProperty('queue_nowait'),
            $this->getProperty('exchange_properties')
        );
        $this->channel->queue_bind($this->queueInfo[0], 'mike.topic', $this->routingKey);
        $this->channel->queue_bind($this->queueInfo[0], 'mike.topic', $this->retryRoutingKey);

        // Declare retry queue
        $this->queueInfo = $this->channel->queue_declare(
            $this->retryQueueName,
            $this->getProperty('queue_passive'),
            $this->getProperty('queue_durable'),
            $this->getProperty('queue_exclusive'),
            $this->getProperty('queue_auto_delete'),
            $this->getProperty('queue_nowait'),
            new AMQPTable(array(
               'x-dead-letter-exchange' => $this->getProperty('exchange')
            ))
        );
        $this->channel->queue_bind($this->queueInfo[0], $this->retryExchange, $this->retryRoutingKey);

        // clear at shutdown
        register_shutdown_function([get_class(), 'shutdown'], $this->channel, $this->connection);
    }
}
