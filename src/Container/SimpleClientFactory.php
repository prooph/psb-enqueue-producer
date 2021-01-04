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

use Enqueue\SimpleClient\SimpleClient;
use Interop\Config\ConfigurationTrait;
use Interop\Config\ProvidesDefaultOptions;
use Interop\Config\RequiresConfigId;
use Prooph\ServiceBus\Exception\InvalidArgumentException;
use Psr\Container\ContainerInterface;

class SimpleClientFactory implements ProvidesDefaultOptions, RequiresConfigId
{
    use ConfigurationTrait;
    /**
     * @var string
     */
    private $simpleClientCallbackName;

    /**
     * Creates a new instance from a specified config, specifically meant to be used as static factory.
     *
     * In case you want to use another config key than provided by the factories, you can add the following factory to
     * your config:
     *
     * <code>
     * <?php
     * return [
     *     'simple_client' => [SimpleClientFactory::class, 'simple_client'],
     * ];
     * </code>
     *
     * @throws InvalidArgumentException
     */
    public static function __callStatic(string $simpleClientCallbackName, array $arguments): SimpleClient
    {
        if (! isset($arguments[0]) || ! $arguments[0] instanceof ContainerInterface) {
            throw new InvalidArgumentException(
                \sprintf('The first argument must be of type %s', ContainerInterface::class)
            );
        }

        return (new static($simpleClientCallbackName))->__invoke($arguments[0]);
    }

    public function __construct(string $simpleClientCallbackName)
    {
        $this->simpleClientCallbackName = $simpleClientCallbackName;
    }

    public function __invoke(ContainerInterface $container): SimpleClient
    {
        $options = $this->options($container->get('config'), $this->simpleClientCallbackName);

        return new SimpleClient($options['config']);
    }

    public function dimensions(): iterable
    {
        return ['enqueue', 'client'];
    }

    public function defaultOptions(): iterable
    {
        return ['config' => []];
    }
}
