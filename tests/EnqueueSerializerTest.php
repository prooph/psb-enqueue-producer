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

use PHPUnit\Framework\TestCase;
use Prooph\Common\Messaging\FQCNMessageFactory;
use Prooph\Common\Messaging\NoOpMessageConverter;
use Prooph\ServiceBus\Message\Enqueue\EnqueueSerializer;
use ProophTest\ServiceBus\Mock\DoSomething;
use ProophTest\ServiceBus\Mock\FetchSomething;
use ProophTest\ServiceBus\Mock\SomethingDone;

/**
 * @group time-sensitive
 */
class EnqueueSerializerTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_allow_serialize_and_unserialize_command(): void
    {
        $command = new DoSomething(['key' => 'value']);

        $serializer = new EnqueueSerializer(new FQCNMessageFactory(), new NoOpMessageConverter());

        $serializedCommand = $serializer->serialize($command);

        $this->assertInternalType('string', $serializedCommand);

        $unserializedCommand = $serializer->unserialize($serializedCommand);

        $this->assertNotSame($command, $unserializedCommand);
        $this->assertEquals($command, $unserializedCommand);
    }

    /**
     * @test
     */
    public function it_should_allow_serialize_and_unserialize_event(): void
    {
        $event = new SomethingDone(['key' => 'value']);

        $serializer = new EnqueueSerializer(new FQCNMessageFactory(), new NoOpMessageConverter());

        $serializedEvent = $serializer->serialize($event);

        $this->assertInternalType('string', $serializedEvent);

        $unserializedEvent = $serializer->unserialize($serializedEvent);

        $this->assertNotSame($event, $unserializedEvent);
        $this->assertEquals($event, $unserializedEvent);
    }

    /**
     * @test
     */
    public function it_should_allow_serialize_and_Unserialize_query(): void
    {
        $query = new FetchSomething(['key' => 'value']);

        $serializer = new EnqueueSerializer(new FQCNMessageFactory(), new NoOpMessageConverter());

        $serializedQuery = $serializer->serialize($query);

        $this->assertInternalType('string', $serializedQuery);

        $unserializedQuery = $serializer->unserialize($serializedQuery);

        $this->assertNotSame($query, $unserializedQuery);
        $this->assertEquals($query, $unserializedQuery);
    }
}
