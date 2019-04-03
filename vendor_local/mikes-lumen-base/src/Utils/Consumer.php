<?php

namespace MikesLumenBase\Utils;

use Illuminate\Support\Str;
use Illuminate\Container\Container;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Bschmitt\Amqp\Facades\Amqp;
use MikesLumenBase\Utils\AmqpRequest;

class Consumer
{

    const INITIAL_EXPIRATION = 60000; // 60s
    const EXPIRATION_FACTOR = 2;
    const EXPIRATION_FACTOR_DEVIATION = 1.1;

    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;


    /**
     * The name of the consumer service.
     *
     * @var string
     */
    protected $consumer;

    /**
     * The name of the publisher servce.
     *
     * @var string
     */
    protected $publisher;

    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen;

    /**
     * Consumer constructor.
     */
    public function __construct(string $consumer, string $publisher, array $listen)
    {
        $this->consumer = $consumer;
        $this->publisher = $publisher;
        $this->listen = $listen;

        $this->container = new Container;
    }


    /**
     * Execute the internal logic of the consume command.
     * Event info is passed to the arguments.
     *
     * @param  string
     * @param  array
     * @param  string
     * @param  string
     * @param  bool
     * @return void
     */
    protected function consume(string $name, array &$payload)
    {
        if (isset($this->listen[$name])) {
            $listener = $this->listen[$name];
            list($class, $method) = Str::parseCallback($listener, 'handle');
            call_user_func_array(
                [$this->container->make($class), $method],
                [&$payload]
            );
        }
    }


    /**
     * Execute the console command.
     * The process listens a work queue forever, and consumes its messages.
     * Each message should have a body with JSON format, otherwise the message will be abandoned.
     * If the internal process raised an exception,
     * the message is republisehd to a retry queue with an expiration.
     * The message is then requeued to the work queue after the expiration delay.
     * The message retry loops infinitetly with exponentially prolonging the expiration
     * until the message suucessfuly processed.
     * Note that messages can be moved to the work queue or can be removed manually in a rabbitmq's admin ui.
     *
     * @return void
     */
    public function handle()
    {
        $request = new AmqpRequest(config());
        $request->setupChannel($this->consumer, $this->publisher);

        Amqp::consume($request->workQueueName, function ($message, $resolver) use ($request) {
            $routingKey = $this->getRoutingKey($message);
            $messageHeaders = $this->getMessageHeaders($message);
            $expiration = $this->getExpiration($messageHeaders);
            $retryCount = $this->getRetryCount($messageHeaders);

            $logInfo = 'routing=' . $routingKey . ', retry=' . $retryCount;

            if (empty($message->body)) {
                \Log::info('[Got message with no body] ' . $logInfo);
                $resolver->acknowledge($message);
                return;
            }

            $payload = json_decode($message->body, true);
            if ($payload) {
                \Log::info('[Got message] ' . $logInfo . (isset($payload['id']) ? ' id=' . $payload['id'] : ''));
                try {
                    $routingPieces = explode(".", $routingKey, 3);
                    if ($routingPieces[0] == '_retry') {
                        $newRoutingKey = $routingPieces[2];
                        $isRetry = true;
                    } else {
                        $newRoutingKey = $routingKey;
                        $isRetry = false;
                    }
                    $this->consume($newRoutingKey, $payload);
                    $resolver->acknowledge($message);
                    \Log::info('[Consumed message] ' . $logInfo . (isset($payload['id']) ? ' id=' . $payload['id'] : ''));
                } catch (\Exception $e) {
                    \Log::error('[Failed to consume] ' . $logInfo . (isset($payload['id']) ? ' id=' . $payload['id'] : '') . ', error=' . $e->getMessage() . ' trace=' . $e->getTraceAsString());

                    $retryHeaders = ['x-retry-count' => $retryCount + 1];

                    $messageForRetry = new AMQPMessage(json_encode($payload), [
                        'content_type' => 'text/plain',
                        'delivery_mode' => 2,
                        'expiration' => $this->getNextExpiration($expiration),
                        'application_headers' => new AMQPTable($retryHeaders)
                    ]);

                    Amqp::publish('_retry.' . $this->consumer . '.' . $newRoutingKey, $messageForRetry, [
                        'exchange' => $request->retryExchange,
                    ]);

                    $resolver->reject($message, false);
                }
            } else {
                \Log::error('[Got message but JSON decode failed] ' . $logInfo);

                // Abandon the broken message
                $resolver->reject($message);
            }
        }, [
            'persistent' => true// required if you want to listen forever
        ]);
    }

    private function getRoutingKey($message)
    {
        return isset($message->delivery_info['routing_key']) ? $message->delivery_info['routing_key'] : '';
    }

    private function getMessageHeaders($message)
    {
        $properties = $message->get_properties();
        return isset($properties['application_headers']) ? $properties['application_headers']->getNativeData() : [];
    }

    private function getRetryCount($messageHeaders)
    {
        return isset($messageHeaders['x-retry-count']) ? $messageHeaders['x-retry-count'] : 0;
    }

    private function getExpiration($messageHeaders)
    {
        return isset($messageHeaders['x-death'][0]['original-expiration']) ? $messageHeaders['x-death'][0]['original-expiration'] : 0;
    }

    private function getNextExpiration($expiration)
    {
        if (empty($expiration)) {
            return self::INITIAL_EXPIRATION;
        }
        $min = self::EXPIRATION_FACTOR;
        $max = $min * self::EXPIRATION_FACTOR_DEVIATION;
        $range = $max - $min;
        $factor = $min + $range * (mt_rand() / mt_getrandmax());
        return round($expiration * $factor);
    }
}
