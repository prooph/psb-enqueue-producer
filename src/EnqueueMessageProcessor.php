<?php

declare(strict_types=1);
namespace Prooph\ServiceBus\Message\Enqueue;

use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Consumption\QueueSubscriberInterface;
use Enqueue\Consumption\Result;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProcessor;
use Enqueue\Util\JSON;
use Prooph\Common\Messaging\Message;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\QueryBus;

final class EnqueueMessageProcessor implements PsrProcessor, QueueSubscriberInterface, CommandSubscriberInterface
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

    /**
     * @param CommandBus $commandBus
     * @param EventBus $eventBus
     * @param QueryBus $queryBus
     * @param EnqueueSerializer $serializer
     */
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
    public function process(PsrMessage $psrMessage, PsrContext $psrContext):Result
    {
        $message = $this->serializer->unserialize($psrMessage->getBody());

        switch ($message->messageType()) {
            case Message::TYPE_EVENT:
                $this->eventBus->dispatch($message);

                break;
            case Message::TYPE_COMMAND:
                $this->commandBus->dispatch($message);

                break;
            case Message::TYPE_QUERY:
                $promise = $this->queryBus->dispatch($message);

                $body = null;
                $promise->then(function ($value) use (&$body) {
                    $body = JSON::encode($value);
                });

                return Result::reply($psrContext->createMessage($body));
            default:
                return Result::reject(sprintf(
                    'The message type "%s" is invalid. The supported types are "%s"',
                    $message->messageType(),
                    implode('", "', [Message::TYPE_COMMAND, Message::TYPE_EVENT, Message::TYPE_QUERY])
                ));
        }

        return Result::ack();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedCommand():array
    {
        return [
            'processorName' => Commands::PROOPH_BUS,
            'queueName' => Commands::PROOPH_BUS,
            'queueNameHardcoded' => true,
            'exclusive' => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedQueues():string
    {
        return Commands::PROOPH_BUS;
    }
}
