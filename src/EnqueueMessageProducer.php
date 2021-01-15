<?php

/**
 * This file is part of prooph/psb-enqueue-producer.
 * (c) 2017-2021 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2017-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 * (c) 2017-2021 Formapro <opensource@forma-pro.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    /**
     * @var string
     */
    private $commandName;

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

        $reply = $this->producer->sendCommand($this->commandName, $enqueueMessage, (bool) $deferred);

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
