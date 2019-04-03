<?php

namespace MikesLumenBase\Utils;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Bschmitt\Amqp\Facades\Amqp;

class Publisher
{
    /**
     * A state before distributed transaction finished.
     *
     * @var string
     */
    const STATUS_PROCESSING = -1;

    /**
     * A state for distributed transaction failed.
     *
     * @var string
     */
    const STATUS_FAILED = -2;

    /**
     * Publish an event directly to rabbitmq
     *
     * @param  string $name Name of the event to be dispatched.
     * @param  array $payload The data to be sent in the event.
     * @param  array $options $options['delay'] set delay seconds to publish.
     *
     * @return void
     */
    public static function publish(string $name, array $payload, array $options = [])
    {
        \Log::info('[Publish] ' . $name . (isset($payload['id']) ? ' id=' . $payload['id'] : ''));

        $delaySeconds = isset($options['delay']) ? $options['delay'] : 0;

        $headers = [];
        if ($delaySeconds > 0) {
            $headers['x-retry-count'] = 0;
        }

        $params = [
            'content_type' => 'text/plain',
            'delivery_mode' => 2,
            'application_headers' => new AMQPTable($headers)
        ];
        if ($delaySeconds > 0) {
            $params['expiration'] = $delaySeconds * 1000;
        }

        $message = new AMQPMessage(json_encode($payload), $params);

        if ($delaySeconds > 0) {
            $amqpData = config('amqp');
            $amqpProperties = $amqpData['properties'][$amqpData['use']];
            Amqp::publish(
                '_retry.' . getenv('APP_NAME') . '.' . $name,
                $message,
                ['exchange' => $amqpProperties['exchange_type'] . '.retry']
            );
        } else {
            Amqp::publish($name, $message);
        }
    }
}
