<?php
namespace Formapro\Prooph\ServiceBus\Message\Enqueue;

use Enqueue\Client\Message as EnqueueMessage;
use Enqueue\Client\ProducerInterface;
use Enqueue\Rpc\TimeoutException;
use Enqueue\Util\JSON;
use Prooph\Common\Messaging\Message;
use Prooph\ServiceBus\Async\MessageProducer;
use React\Promise\Deferred;
use React\Promise\LazyPromise;
use React\Promise\Promise;

class EnqueueMessageProducer implements MessageProducer
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

    /**
     * @param ProducerInterface $producer
     * @param EnqueueSerializer $serializer
     * @param int $replyTimeout
     */
    public function __construct(ProducerInterface $producer, EnqueueSerializer $serializer, $replyTimeout = 30000)
    {
        $this->producer = $producer;
        $this->serializer = $serializer;
        $this->replyTimeout = $replyTimeout;
    }

    /**
     * @inheritdoc
     */
    public function __invoke(Message $message, Deferred $deferred = null):void
    {
        $needReply = (bool) $deferred;

        $enqueueMessage = new EnqueueMessage($this->serializer->serialize($message));
        $enqueueMessage->setContentType('application/json');

        if ($message instanceof DelayedMessage) {
            $enqueueMessage->setDelay($message->delay());
        }

        $reply = $this->producer->sendCommand(Commands::PROOPH_BUS, $enqueueMessage, $needReply);

        if ($needReply) {
            try {
                $value = JSON::decode($reply->receive($this->replyTimeout)->getBody());

                $deferred->resolve($value);
            } catch (TimeoutException $e) {
                $deferred->reject($e->getMessage());
            }
        }
    }
}
