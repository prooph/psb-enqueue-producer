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
use Prooph\Common\Messaging\FQCNMessageFactory;
use Prooph\Common\Messaging\NoOpMessageConverter;
use Prooph\ServiceBus\Exception\InvalidArgumentException;
use Prooph\ServiceBus\Message\Enqueue\EnqueueSerializer;
use Psr\Container\ContainerInterface;

class EnqueueSerializerFactory implements ProvidesDefaultOptions, RequiresConfigId
{
    use ConfigurationTrait;
    /**
     * @var string
     */
    private $serializerCallbackName;

    /**
     * Creates a new instance from a specified config, specifically meant to be used as static factory.
     *
     * In case you want to use another config key than provided by the factories, you can add the following factory to
     * your config:
     *
     * <code>
     * <?php
     * return [
     *     'serializer' => [EnqueueSerializerFactory::class, 'serializer_name'],
     * ];
     * </code>
     *
     * @throws InvalidArgumentException
     */
    public static function __callStatic(string $serializerCallbackName, array $arguments): EnqueueSerializer
    {
        if (! isset($arguments[0]) || ! $arguments[0] instanceof ContainerInterface) {
            throw new InvalidArgumentException(
                \sprintf('The first argument must be of type %s', ContainerInterface::class)
            );
        }

        return (new static($serializerCallbackName))->__invoke($arguments[0]);
    }

    public function __construct(string $serializerCallbackName)
    {
        $this->serializerCallbackName = $serializerCallbackName;
    }

    public function __invoke(ContainerInterface $container): EnqueueSerializer
    {
        $options = $this->options($container->get('config'), $this->serializerCallbackName);

        return new EnqueueSerializer(
            $container->get($options['message_factory']),
            $container->get($options['message_converter'])
        );
    }

    public function dimensions(): iterable
    {
        return ['prooph', 'enqueue-producer', 'serializer'];
    }

    public function defaultOptions(): iterable
    {
        return [
            'message_converter' => NoOpMessageConverter::class,
            'message_factory' => FQCNMessageFactory::class,
        ];
    }
}
