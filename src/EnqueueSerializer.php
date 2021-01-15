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

namespace Prooph\ServiceBus\Message\Enqueue;

use Enqueue\Util\JSON;
use Prooph\Common\Messaging\Message;
use Prooph\Common\Messaging\MessageConverter;
use Prooph\Common\Messaging\MessageDataAssertion;
use Prooph\Common\Messaging\MessageFactory;

class EnqueueSerializer
{
    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * @var MessageConverter
     */
    private $messageConverter;

    /**
     * @param MessageFactory $messageFactory
     * @param MessageConverter $messageConverter
     */
    public function __construct(MessageFactory $messageFactory, MessageConverter $messageConverter)
    {
        $this->messageFactory = $messageFactory;
        $this->messageConverter = $messageConverter;
    }

    public function serialize(Message $message): string
    {
        $messageData = $this->messageConverter->convertToArray($message);

        MessageDataAssertion::assert($messageData);

        $messageData['created_at'] = $message->createdAt()->format('Y-m-d\TH:i:s.u');

        return \json_encode([
            'message' => $messageData,
            'timestamp' => \time(),
        ]);
    }

    public function unserialize(string $rawMessage): Message
    {
        $data = JSON::decode($rawMessage);

        $messageData = $data['message'];

        $messageData['created_at'] = \DateTimeImmutable::createFromFormat(
            'Y-m-d\TH:i:s.u',
            $messageData['created_at'],
            new \DateTimeZone('UTC')
        );

        return $this->messageFactory->createMessageFromArray($messageData['message_name'], $messageData);
    }
}
