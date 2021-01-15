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

namespace ProophTest\ServiceBus\Enqueue\Functional;

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Enqueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Enqueue\SimpleClient\SimpleClient;
use PHPUnit\Framework\TestCase;
use Prooph\Common\Messaging\FQCNMessageFactory;
use Prooph\Common\Messaging\NoOpMessageConverter;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\Message\Enqueue\EnqueueMessageProcessor;
use Prooph\ServiceBus\Message\Enqueue\EnqueueMessageProducer;
use Prooph\ServiceBus\Message\Enqueue\EnqueueSerializer;
use Prooph\ServiceBus\Plugin\Router\CommandRouter;
use Prooph\ServiceBus\Plugin\Router\EventRouter;
use Prooph\ServiceBus\QueryBus;
use ProophTest\ServiceBus\Mock\DoSomething;
use ProophTest\ServiceBus\Mock\MessageHandler;
use ProophTest\ServiceBus\Mock\SomethingDone;
use Symfony\Component\Filesystem\Filesystem;

class EnqueueMessageProducerTest extends TestCase
{
    /**
     * @var SimpleClient
     */
    private $client;

    /**
     * @var EnqueueSerializer
     */
    private $serializer;

    protected function setUp(): void
    {
        (new Filesystem())->remove(__DIR__.'/queues/');

        $this->client = new SimpleClient('file://'.__DIR__.'/queues');

        $this->client->getQueueConsumer()->setReceiveTimeout(1);

        $this->serializer = new EnqueueSerializer(new FQCNMessageFactory(), new NoOpMessageConverter());
    }

    /**
     * @test
     */
    public function it_sends_a_command_to_queue_pulls_it_with_consumer_and_forwards_it_to_command_bus(): void
    {
        $command = new DoSomething(['data' => 'test command']);

        //The message dispatcher works with a ready-to-use enqueue producer and one queue
        $messageProducer = new EnqueueMessageProducer($this->client->getProducer(), $this->serializer, 'prooph_bus', 2000);

        //Set up command bus which will receive the command message from the enqueue consumer
        $consumerCommandBus = new CommandBus();

        $doSomethingHandler = new MessageHandler();

        $router = new CommandRouter();
        $router->route($command->messageName())->to($doSomethingHandler);
        $router->attachToMessageBus($consumerCommandBus);

        $enqueueProcessor = new EnqueueMessageProcessor($consumerCommandBus, new EventBus(), new QueryBus(), $this->serializer);
        $this->client->bindCommand('prooph_bus', $enqueueProcessor);

        //Normally you would send the command on a command bus. We skip this step here cause we are only
        //interested in the function of the message dispatcher
        $messageProducer($command);

        $this->client->consume(new ChainExtension([
            new LimitConsumedMessagesExtension(2),
            new LimitConsumptionTimeExtension(new \DateTime('now + 1 seconds')),
        ]));

        $this->assertNotNull($doSomethingHandler->getLastMessage());

        $this->assertEquals($command->payload(), $doSomethingHandler->getLastMessage()->payload());
    }

    /**
     * @test
     */
    public function it_sends_an_event_to_queue_pulls_it_with_consumer_and_forwards_it_to_event_bus(): void
    {
        $event = new SomethingDone(['data' => 'test event']);

        //The message dispatcher works with a ready-to-use enqueue producer and one queue
        $messageProducer = new EnqueueMessageProducer($this->client->getProducer(), $this->serializer, 'prooph_bus', 2000);

        //Set up event bus which will receive the event message from the enqueue consumer
        $consumerEventBus = new EventBus();

        $somethingDoneListener = new MessageHandler();

        $router = new EventRouter();
        $router->route($event->messageName())->to($somethingDoneListener);
        $router->attachToMessageBus($consumerEventBus);

        $enqueueProcessor = new EnqueueMessageProcessor(new CommandBus(), $consumerEventBus, new QueryBus(), $this->serializer);
        $this->client->bindCommand('prooph_bus', $enqueueProcessor);

        //Normally you would send the event on a event bus. We skip this step here cause we are only
        //interested in the function of the message dispatcher
        $messageProducer($event);

        $this->client->consume(new ChainExtension([
            new LimitConsumedMessagesExtension(2),
            new LimitConsumptionTimeExtension(new \DateTime('now + 1 seconds')),
        ]));

        $this->assertNotNull($somethingDoneListener->getLastMessage());

        $this->assertEquals($event->payload(), $somethingDoneListener->getLastMessage()->payload());
    }
}
