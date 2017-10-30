<?php
/**
 * This file is part of the prooph/psb-enqueue-producer.
 * (c) 2017-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2017-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 * (c) 2017-2017 Formapro <opensource@forma-pro.com>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Prooph\ServiceBus\Message\Enqueue;

use Enqueue\Client\Message as EnqueueMessage;
use Enqueue\Client\ProducerInterface;
use Enqueue\Rpc\Promise;
use Enqueue\Rpc\TimeoutException;
use Enqueue\Util\JSON;
use Prooph\Common\Messaging\Message;
use Prooph\ServiceBus\Async\MessageProducer;
use React\Promise\Deferred;

class EnqueueMessageProducer implements MessageProducer
{
    /**
     * @var ProducerInterface
     */
    protected $producer;

    /**
     * @var EnqueueSerializer
     */
    protected $serializer;

    /**
     * @var int
     */
    protected $replyTimeout;

    /**
     * @var string
     */
    protected $commandName;

    public function __construct(ProducerInterface $producer, EnqueueSerializer $serializer, string $commandName, int $replyTimeout)
    {
        $this->producer = $producer;
        $this->serializer = $serializer;
        $this->commandName = $commandName;
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

        $reply = $this->sendCommand($deferred, $enqueueMessage, $message);

        if (null !== $deferred) {
            try {
                $value = JSON::decode($reply->receive($this->replyTimeout)->getBody());

                $deferred->resolve($value);
            } catch (TimeoutException $e) {
                $deferred->reject($e->getMessage());
            }
        }
    }

    protected function sendCommand(?Deferred $deferred, EnqueueMessage $enqueueMessage, Message $message): ?Promise
    {
        return $this->producer->sendCommand($this->commandName, $enqueueMessage, (bool) $deferred);
    }
}

