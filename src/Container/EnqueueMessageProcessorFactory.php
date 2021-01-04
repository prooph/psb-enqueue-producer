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

namespace Prooph\ServiceBus\Message\Enqueue\Container;

use Interop\Config\ConfigurationTrait;
use Interop\Config\ProvidesDefaultOptions;
use Interop\Config\RequiresConfigId;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\Exception\InvalidArgumentException;
use Prooph\ServiceBus\Message\Enqueue\EnqueueMessageProcessor;
use Prooph\ServiceBus\Message\Enqueue\EnqueueSerializer;
use Prooph\ServiceBus\QueryBus;
use Psr\Container\ContainerInterface;

class EnqueueMessageProcessorFactory implements ProvidesDefaultOptions, RequiresConfigId
{
    use ConfigurationTrait;
    /**
     * @var string
     */
    private $messageProducerCallbackName;

    /**
     * Creates a new instance from a specified config, specifically meant to be used as static factory.
     *
     * In case you want to use another config key than provided by the factories, you can add the following factory to
     * your config:
     *
     * <code>
     * <?php
     * return [
     *     'message_producer' => [EnqueueMessageProcessorFactory::class, 'message_producer_name'],
     * ];
     * </code>
     *
     * @throws InvalidArgumentException
     */
    public static function __callStatic(string $messageProducerCallbackName, array $arguments): EnqueueMessageProcessor
    {
        if (! isset($arguments[0]) || ! $arguments[0] instanceof ContainerInterface) {
            throw new InvalidArgumentException(
                \sprintf('The first argument must be of type %s', ContainerInterface::class)
            );
        }

        return (new static($messageProducerCallbackName))->__invoke($arguments[0]);
    }

    public function __construct(string $messageProducerCallbackName)
    {
        $this->messageProducerCallbackName = $messageProducerCallbackName;
    }

    public function __invoke(ContainerInterface $container): EnqueueMessageProcessor
    {
        $options = $this->options($container->get('config'), $this->messageProducerCallbackName);

        return new EnqueueMessageProcessor(
            $container->get($options['command_bus']),
            $container->get($options['event_bus']),
            $container->get($options['query_bus']),
            $container->get($options['serializer'])
        );
    }

    public function dimensions(): iterable
    {
        return ['prooph', 'enqueue-producer', 'message_processor'];
    }

    public function defaultOptions(): iterable
    {
        return [
            'command_bus' => CommandBus::class,
            'event_bus' => EventBus::class,
            'query_bus' => QueryBus::class,
            'serializer' => EnqueueSerializer::class,
        ];
    }
}
