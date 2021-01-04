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

use PHPUnit\Framework\TestCase;
use Prooph\Common\Messaging\MessageConverter;
use Prooph\Common\Messaging\MessageFactory;
use Prooph\ServiceBus\Exception\InvalidArgumentException;
use Prooph\ServiceBus\Message\Enqueue\Container\EnqueueSerializerFactory;
use Prooph\ServiceBus\Message\Enqueue\EnqueueSerializer;
use Psr\Container\ContainerInterface;

class EnqueueSerializerFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_message_producer(): void
    {
        $messageConverter = $this->prophesize(MessageConverter::class);
        $messageFactory = $this->prophesize(MessageFactory::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn([
            'prooph' => [
                'enqueue-producer' => [
                    'serializer' => [
                        'test-serializer' => [
                            'message_factory' => 'test-message-factory',
                            'message_converter' => 'test-message-converter',
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();
        $container->get('test-message-factory')->willReturn($messageFactory->reveal())->shouldBeCalled();
        $container->get('test-message-converter')->willReturn($messageConverter->reveal())->shouldBeCalled();

        $name = 'test-serializer';
        $serializer = EnqueueSerializerFactory::$name($container->reveal());
        $this->assertInstanceOf(EnqueueSerializer::class, $serializer);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_no_container_passed_to_call_static(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The first argument must be of type ' . ContainerInterface::class);

        $name = 'test-serializer';
        EnqueueSerializerFactory::$name('invalid_container');
    }
}
