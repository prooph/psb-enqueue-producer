<?php

declare(strict_types=1);

namespace Prooph\ServiceBus\Message\Enqueue;

use Prooph\Common\Messaging\Message;

/**
 * TODO copy pasted from https://github.com/prooph/humus-amqp-producer/blob/master/src/DelayedMessage.php
 *
 * Interface to represent delayed messages (aka messages that are processed in the future instead of now)
 * Usually you would implement these for commands that should be executed at a later time
 */
interface DelayedMessage extends Message
{
    /**
     * @return int the delay in milliseconds
     */
    public function delay(): int;
}
