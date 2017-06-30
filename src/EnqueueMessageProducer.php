<?php

declare(strict_types=1);
namespace Prooph\ServiceBus\Message\Enqueue;

use Enqueue\Client\Message as EnqueueMessage;
use Enqueue\Client\ProducerInterface;
use Enqueue\Rpc\TimeoutException;
use Enqueue\Util\JSON;
use Prooph\Common\Messaging\Message;
use Prooph\ServiceBus\Async\MessageProducer;
use React\Promise\Deferred;

final class EnqueueMessageProducer implements MessageProducer
{
    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var EnqueueSerializer
     */
    private $serializer;

    /**
     * @var int
     */
    private $replyTimeout;

    public function __construct(ProducerInterface $producer, EnqueueSerializer $serializer, int $replyTimeout)
    {
        $this->producer = $producer;
        $this->serializer = $serializer;
        $this->replyTimeout = $replyTimeout;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Message $message, Deferred $deferred = null): void
    {
        $enqueueMessage = new EnqueueMessage($this->serializer->serialize($message));
        $enqueueMessage->setContentType('application/json');

        if ($message instanceof DelayedMessage) {
            $enqueueMessage->setDelay((int) $message->delay() / 1000);
        }

        $reply = $this->producer->sendCommand(Commands::PROOPH_BUS, $enqueueMessage, (bool) $deferred);

        if (null !== $deferred) {
            try {
                $value = JSON::decode($reply->receive($this->replyTimeout)->getBody());

                $deferred->resolve($value);
            } catch (TimeoutException $e) {
                $deferred->reject($e->getMessage());
            }
        }
    }
}
