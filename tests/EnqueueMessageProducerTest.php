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

use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TraceableProducer;
use Enqueue\Null\NullMessage;
use Enqueue\Rpc\Promise;
use Enqueue\Rpc\TimeoutException;
use PHPUnit\Framework\TestCase;
use Prooph\Common\Messaging\Message;
use Prooph\ServiceBus\Message\Enqueue\DelayedMessage;
use Prooph\ServiceBus\Message\Enqueue\EnqueueMessageProducer;
use Prooph\ServiceBus\Message\Enqueue\EnqueueSerializer;
use React\Promise\Deferred;

class EnqueueMessageProducerTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_send_message_without_delay(): void
    {
        $message = $this->createProophMessageMock();

        $serializer = $this->createSerializerMock();
        $serializer
        ->expects($this->once())
            ->method('serialize')
            ->with($this->identicalTo($message))
            ->willReturn('theSerializedMessage');

        $enqueueProducer = $this->createProducer();

        $producer = new EnqueueMessageProducer(
            $enqueueProducer,
            $serializer,
            'prooph_bus',
            30000
        );

        $producer($message);

        $traces = $enqueueProducer->getCommandTraces('prooph_bus');

        $this->assertCount(1, $traces);
        $this->assertEquals('theSerializedMessage', $traces[0]['body']);
        $this->assertEquals('application/json', $traces[0]['contentType']);
        $this->assertNull($traces[0]['delay']);
    }

    /**
     * @test
     */
    public function it_should_send_message_with_delay(): void
    {
        $message = $this->createMock(DelayedMessage::class);
        $message
            ->expects($this->once())
            ->method('delay')
            ->willReturn(12345);

        $serializer = $this->createSerializerMock();
        $serializer
            ->expects($this->once())
            ->method('serialize')
            ->with($this->identicalTo($message))
            ->willReturn('theSerializedMessage');

        $enqueueProducer = $this->createProducer();

        $producer = new EnqueueMessageProducer(
            $enqueueProducer,
            $serializer,
            'prooph_bus',
            30000
        );

        $producer($message);

        $traces = $enqueueProducer->getCommandTraces('prooph_bus');

        $this->assertCount(1, $traces);
        $this->assertEquals('theSerializedMessage', $traces[0]['body']);
        $this->assertEquals('application/json', $traces[0]['contentType']);
        $this->assertEquals(12.345, $traces[0]['delay']);
    }

    /**
     * @test
     */
    public function it_should_send_message_with_deferred(): void
    {
        $message = $this->createProophMessageMock();

        $serializer = $this->createSerializerMock();
        $serializer
            ->expects($this->once())
            ->method('serialize')
            ->with($this->identicalTo($message))
            ->willReturn('theSerializedMessage');

        $enqueuePromise = $this->createMock(Promise::class);
        $enqueuePromise
            ->expects($this->once())
            ->method('receive')
            ->with(30000)
            ->willReturn(new NullMessage('{"foo": "fooVal"}'));

        $enqueueProducer = $this->createReplyProducer($enqueuePromise);

        $producer = new EnqueueMessageProducer(
            $enqueueProducer,
            $serializer,
            'prooph_bus',
            30000
        );

        $deferred = $this->createMock(Deferred::class);
        $deferred
            ->expects($this->once())
            ->method('resolve')
            ->with(['foo' => 'fooVal']);

        $producer($message, $deferred);

        $traces = $enqueueProducer->getCommandTraces('prooph_bus');

        $this->assertCount(1, $traces);
        $this->assertEquals('theSerializedMessage', $traces[0]['body']);
        $this->assertEquals('application/json', $traces[0]['contentType']);
    }

    /**
     * @test
     */
    public function it_should_send_message_with_deferred_and_reply_timeout(): void
    {
        $message = $this->createProophMessageMock();

        $serializer = $this->createSerializerMock();
        $serializer
            ->expects($this->once())
            ->method('serialize')
            ->with($this->identicalTo($message))
            ->willReturn('theSerializedMessage');

        $enqueuePromise = $this->createMock(Promise::class);
        $enqueuePromise
            ->expects($this->once())
            ->method('receive')
            ->willThrowException(new TimeoutException('The RPC call is time-outed'));

        $enqueueProducer = $this->createReplyProducer($enqueuePromise);

        $producer = new EnqueueMessageProducer(
            $enqueueProducer,
            $serializer,
            'prooph_bus',
            30000
        );

        $deferred = $this->createMock(Deferred::class);
        $deferred
            ->expects($this->once())
            ->method('reject')
            ->with('The RPC call is time-outed');

        $producer($message, $deferred);

        $traces = $enqueueProducer->getCommandTraces('prooph_bus');

        $this->assertCount(1, $traces);
        $this->assertEquals('theSerializedMessage', $traces[0]['body']);
        $this->assertEquals('application/json', $traces[0]['contentType']);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Message
     */
    private function createProophMessageMock(): Message
    {
        return $this->createMock(Message::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EnqueueSerializer
     */
    private function createSerializerMock(): EnqueueSerializer
    {
        return $this->createMock(EnqueueSerializer::class);
    }

    private function createProducer(): TraceableProducer
    {
        $producerMock = $this->createMock(ProducerInterface::class);
        $producerMock
            ->expects($this->once())
            ->method('sendCommand')
            ->with($this->anything(), $this->anything(), false)
            ->willReturn(null);

        return new TraceableProducer($producerMock);
    }

    private function createReplyProducer(Promise $promise): TraceableProducer
    {
        $producerMock = $this->createMock(ProducerInterface::class);
        $producerMock
            ->expects($this->once())
            ->method('sendCommand')
            ->with($this->anything(), $this->anything(), true)
            ->willReturn($promise);

        return new TraceableProducer($producerMock);
    }
}
