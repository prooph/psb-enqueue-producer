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
use Prooph\ServiceBus\Message\Enqueue\Container\EnqueueMessageProducerFactory;
use Prooph\ServiceBus\Message\Enqueue\EnqueueMessageProducer;
use Prooph\ServiceBus\Message\Enqueue\EnqueueSerializer;
use Psr\Container\ContainerInterface;

class EnqueueMessageProducerFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_message_producer(): void
    {
        // cannot mock it, cuz it's final.
        $client = new SimpleClient('null://');

        $serializer = $this->prophesize(EnqueueSerializer::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn([
            'prooph' => [
                'enqueue-producer' => [
                    'message_producer' => [
                        'test-message-producer' => [
                            'client' => 'test-simple-client',
                            'serializer' => 'test-serializer',
                            'command_name' => 'test-command-name',
                            'reply_timeout' => 12345,
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();
        $container->get('test-simple-client')->willReturn($client)->shouldBeCalled();
        $container->get('test-serializer')->willReturn($serializer->reveal())->shouldBeCalled();

        $name = 'test-message-producer';
        $messageProducer = EnqueueMessageProducerFactory::$name($container->reveal());
        $this->assertInstanceOf(EnqueueMessageProducer::class, $messageProducer);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_no_container_passed_to_call_static(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The first argument must be of type ' . ContainerInterface::class);

        $name = 'test-message-producer';
        EnqueueMessageProducerFactory::$name('invalid_container');
    }
}
