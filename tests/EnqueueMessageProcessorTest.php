<?php

/**
 * This file is part of the prooph/psb-enqueue-producer.
 * (c) 2016-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2016-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 * (c) 2017 Maksym Kotliar <kotlyar.maksim@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\ServiceBus;

use Enqueue\Consumption\Result;
use Enqueue\Null\NullContext;
use Enqueue\Null\NullMessage;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProcessor;
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
    public function testShouldImplementPsrProcessorInterface()
    {
        $rc = new \ReflectionClass(EnqueueMessageProcessor::class);

        $this->assertTrue($rc->implementsInterface(PsrProcessor::class));
    }

    public function testShouldRejectMessageWithInvalidMessageType()
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

    public function testShouldProxyCommandToCommandBusAndReturnAck()
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

    public function testShouldProxyEventToEventBusAndReturnAck()
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

    public function testShouldProxyQueryToQueryBusAndReturnReply()
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
                call_user_func($callback, 'theReply');
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
    private function createCommandBusMock()
    {
        return $this->createMock(CommandBus::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EventBus
     */
    private function createEventBusMock()
    {
        return $this->createMock(EventBus::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QueryBus
     */
    private function createQueryBusMock()
    {
        return $this->createMock(QueryBus::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EnqueueSerializer
     */
    private function createSerializerMock()
    {
        return $this->createMock(EnqueueSerializer::class);
    }
}
