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

namespace ProophTest\ServiceBus\Enqueue;

use Enqueue\Consumption\Result;
use Enqueue\Null\NullContext;
use Enqueue\Null\NullMessage;
use Interop\Queue\Message as PsrMessage;
use Interop\Queue\Processor as PsrProcessor;
use PHPUnit\Framework\TestCase;
use Prooph\Common\Messaging\DomainMessage;
use Prooph\Common\Messaging\PayloadTrait;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\Message\Enqueue\EnqueueMessageProcessor;
use Prooph\ServiceBus\Message\Enqueue\EnqueueSerializer;
use Prooph\ServiceBus\QueryBus;
use ProophTest\ServiceBus\Mock\DoSomething;
use ProophTest\ServiceBus\Mock\FetchSomething;
use ProophTest\ServiceBus\Mock\SomethingDone;
use React\Promise\Promise;

final class EnqueueMessageProcessorTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_implement_psr_processor_interface(): void
    {
        $rc = new \ReflectionClass(EnqueueMessageProcessor::class);

        $this->assertTrue($rc->implementsInterface(PsrProcessor::class));
    }

    /**
     * @test
     */
    public function it_should_reject_message_with_invalid_message_type(): void
    {
        $serializerMock = $this->createSerializerMock();
        $serializerMock
            ->expects($this->once())
            ->method('unserialize')
            ->with('rawInvalidMessage')
            ->willReturn(new class(['key' => 'value']) extends DomainMessage {
                use PayloadTrait;

                public function messageType(): string
                {
                    return 'invalidType';
                }
            });

        $processor = new EnqueueMessageProcessor(
            $this->createCommandBusMock(),
            $this->createEventBusMock(),
            $this->createQueryBusMock(),
            $serializerMock
        );

        $result = $processor->process(new NullMessage('rawInvalidMessage'), new NullContext());

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::REJECT, $result->getStatus());
        $this->assertEquals(
            'The message type "invalidType" is invalid. The supported types are "command", "event", "query"',
            $result->getReason()
        );
    }

    /**
     * @test
     */
    public function it_should_proxy_command_to_command_bus_and_return_ack(): void
    {
        $command = new DoSomething(['key' => 'value']);

        $serializerMock = $this->createSerializerMock();
        $serializerMock
            ->expects($this->once())
            ->method('unserialize')
            ->with('rawCommand')
            ->willReturn($command);

        $commandBusMock = $this->createCommandBusMock();
        $commandBusMock
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->identicalTo($command));

        $processor = new EnqueueMessageProcessor(
            $commandBusMock,
            $this->createEventBusMock(),
            $this->createQueryBusMock(),
            $serializerMock
        );

        $result = $processor->process(new NullMessage('rawCommand'), new NullContext());

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::ACK, $result->getStatus());
        $this->assertNull($result->getReply());
    }

    /**
     * @test
     */
    public function it_should_proxy_event_to_event_bus_and_return_ack(): void
    {
        $event = new SomethingDone(['data' => 'test event']);

        $serializerMock = $this->createSerializerMock();
        $serializerMock
            ->expects($this->once())
            ->method('unserialize')
            ->with('rawEvent')
            ->willReturn($event);

        $eventBusMock = $this->createEventBusMock();
        $eventBusMock
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->identicalTo($event));

        $processor = new EnqueueMessageProcessor(
            $this->createCommandBusMock(),
            $eventBusMock,
            $this->createQueryBusMock(),
            $serializerMock
        );

        $result = $processor->process(new NullMessage('rawEvent'), new NullContext());

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::ACK, $result->getStatus());
        $this->assertNull($result->getReply());
    }

    /**
     * @test
     */
    public function it_should_proxy_query_to_query_bus_and_return_reply(): void
    {
        $query = new FetchSomething(['data' => 'test query']);

        $serializerMock = $this->createSerializerMock();
        $serializerMock
            ->expects($this->once())
            ->method('unserialize')
            ->with('rawQuery')
            ->willReturn($query);

        $promiseMock = $this->createMock(Promise::class);
        $promiseMock
            ->expects($this->once())
            ->method('then')
            ->willReturnCallback(function ($callback) {
                \call_user_func($callback, 'theReply');
            });

        $queryBusMock = $this->createQueryBusMock();
        $queryBusMock
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->identicalTo($query))
            ->willReturn($promiseMock);

        $processor = new EnqueueMessageProcessor(
            $this->createCommandBusMock(),
            $this->createEventBusMock(),
            $queryBusMock,
            $serializerMock
        );

        $result = $processor->process(new NullMessage('rawQuery'), new NullContext());

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::ACK, $result->getStatus());
        $this->assertInstanceOf(PsrMessage::class, $result->getReply());
        $this->assertEquals('"theReply"', $result->getReply()->getBody());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|CommandBus
     */
    private function createCommandBusMock(): CommandBus
    {
        return $this->createMock(CommandBus::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EventBus
     */
    private function createEventBusMock(): EventBus
    {
        return $this->createMock(EventBus::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QueryBus
     */
    private function createQueryBusMock(): QueryBus
    {
        return $this->createMock(QueryBus::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EnqueueSerializer
     */
    private function createSerializerMock(): EnqueueSerializer
    {
        return $this->createMock(EnqueueSerializer::class);
    }
}
