Enqueue Message Producer for Prooph Service Bus
===============================================

Use [Enqueue](https://github.com/php-enqueue/enqueue-dev) as a message producer for [Prooph Service Bus](https://github.com/prooph/service-bus).

## Installation

You can install the producer via composer by executing `$ composer require prooph/psb-enqueue-producer`.

## Usage

Check the [EnqueueMessageProducerTest](tests/Functional/EnqueueMessageProducerTest.php). Set up the producer is a straightforward task. Most of
the required components are provided by PSB and Enqueue. This package only provides the glue code needed to let both
systems work together.

## Why Enqueue producer

* You choose a transport from [many supported ones](https://github.com/php-enqueue/enqueue-dev/tree/master/docs/transport).
* Could be used from Symfony as well as plain PHP.
* Supports delayed messages (if transport supports it).
* Supports events, commands and queues with possibility to get result.
* Simple and clean code. 

## Support

- Ask questions on Stack Overflow tagged with [#prooph](https://stackoverflow.com/questions/tagged/prooph).
- Ask enqueue questions in [enqueue](https://gitter.im/php-enqueue/Lobby) gitter chat.
- File issues at [https://github.com/prooph/psb-enqueue-producer/issues](https://github.com/prooph/psb-enqueue-producer/issues).
- Say hello in the [prooph gitter](https://gitter.im/prooph/improoph) chat.

## License

It is released under the [MIT License](LICENSE).
