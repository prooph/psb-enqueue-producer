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

namespace ProophTest\ServiceBus\Message\Enqueue\Container;

use Enqueue\SimpleClient\SimpleClient;
use PHPUnit\Framework\TestCase;
use Prooph\ServiceBus\Exception\InvalidArgumentException;
use Prooph\ServiceBus\Message\Enqueue\Container\SimpleClientFactory;
use Psr\Container\ContainerInterface;

class SimpleClientFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_message_producer(): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn([
            'enqueue' => [
                'client' => [
                    'test-client' => [
                        'config' => 'null://',
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $name = 'test-client';
        $client = SimpleClientFactory::$name($container->reveal());
        $this->assertInstanceOf(SimpleClient::class, $client);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_no_container_passed_to_call_static(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The first argument must be of type ' . ContainerInterface::class);

        $name = 'test-serializer';
        SimpleClientFactory::$name('invalid_container');
    }
}
