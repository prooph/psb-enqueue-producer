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
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\Exception\InvalidArgumentException;
use Prooph\ServiceBus\Message\Enqueue\Container\EnqueueMessageProcessorFactory;
use Prooph\ServiceBus\Message\Enqueue\EnqueueMessageProcessor;
use Prooph\ServiceBus\Message\Enqueue\EnqueueSerializer;
use Prooph\ServiceBus\QueryBus;
use Psr\Container\ContainerInterface;

class EnqueueMessageProcessorFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_enqueue_message_processor(): void
    {
        $commandBus = $this->prophesize(CommandBus::class);
        $eventBus = $this->prophesize(EventBus::class);
        $queryBus = $this->prophesize(QueryBus::class);
        $serializer = $this->prophesize(EnqueueSerializer::class);

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn([
            'prooph' => [
                'enqueue-producer' => [
                    'message_processor' => [
                        'test-message-processor' => [
                            'command_bus' => 'test-command-bus',
                            'event_bus' => 'test-event-bus',
                            'query_bus' => 'test-query-bus',
                            'serializer' => 'test-serializer',
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $container->get('test-command-bus')->willReturn($commandBus->reveal())->shouldBeCalled();
        $container->get('test-event-bus')->willReturn($eventBus->reveal())->shouldBeCalled();
        $container->get('test-query-bus')->willReturn($queryBus->reveal())->shouldBeCalled();
        $container->get('test-serializer')->willReturn($serializer->reveal())->shouldBeCalled();

        $name = 'test-message-processor';
        $messageProcessorCallback = EnqueueMessageProcessorFactory::$name($container->reveal());
        $this->assertInstanceOf(EnqueueMessageProcessor::class, $messageProcessorCallback);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_no_container_passed_to_call_static(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The first argument must be of type ' . ContainerInterface::class);

        $name = 'test-event-consumer-callback';
        EnqueueMessageProcessorFactory::$name('invalid_container');
    }
}
