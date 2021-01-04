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

use Enqueue\Consumption\Result;
use Enqueue\Util\JSON;
use Interop\Queue\Context as PsrContext;
use Interop\Queue\Message as PsrMessage;
use Interop\Queue\Processor as PsrProcessor;
use Prooph\Common\Messaging\Message;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\QueryBus;

final class EnqueueMessageProcessor implements PsrProcessor
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var EventBus
     */
    private $eventBus;

    /**
     * @var QueryBus
     */
    private $queryBus;

    /**
     * @var EnqueueSerializer
     */
    private $serializer;

    public function __construct(CommandBus $commandBus, EventBus $eventBus, QueryBus $queryBus, EnqueueSerializer $serializer)
    {
        $this->commandBus = $commandBus;
        $this->eventBus = $eventBus;
        $this->queryBus = $queryBus;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(PsrMessage $psrMessage, PsrContext $psrContext): Result
    {
        $message = $this->serializer->unserialize($psrMessage->getBody());

        switch ($message->messageType()) {
            case Message::TYPE_COMMAND:
                $this->commandBus->dispatch($message);

                break;
            case Message::TYPE_EVENT:
                $this->eventBus->dispatch($message);

                break;
            case Message::TYPE_QUERY:
                $promise = $this->queryBus->dispatch($message);

                $body = '';
                $promise->then(function ($value) use (&$body) {
                    $body = JSON::encode($value);
                });

                return Result::reply($psrContext->createMessage($body));
            default:
                return Result::reject(\sprintf(
                    'The message type "%s" is invalid. The supported types are "%s"',
                    $message->messageType(),
                    \implode('", "', [Message::TYPE_COMMAND, Message::TYPE_EVENT, Message::TYPE_QUERY])
                ));
        }

        return Result::ack();
    }
}
