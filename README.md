Enqueue Message Producer for Prooph Service Bus
===============================================

Use [Enqueue](https://github.com/php-enqueue/enqueue-dev) as a message producer for [Prooph Service Bus](https://github.com/prooph/service-bus).

## Installation

You can install the producer via composer by executing `$ composer require formapro/psb-enqueue-producer`.

## Usage

Check the [EnqueueMessageProducerTest](tests/EnqueueMessageProducerTest.php). Set up the producer is a straightforward task. Most of
the required components are provided by PSB and Enqueue. This package only provides the glue code needed to let both
systems work together.

## Why Enqueue producer

* You choose a transport from [many supported ones](https://github.com/php-enqueue/enqueue-dev/tree/master/docs/transport).
* Could be used from Symfony as well as plain PHP.
* Supports delayed messages (if transport supports it).
* Supports events, commands and queues with possibility to get result.
* Simple and clean code. 

## Support

- Ask prooph questions on [prooph-users](https://groups.google.com/forum/?hl=de#!forum/prooph) google group.
- Ask enqueue questions in [enqueue](https://gitter.im/php-enqueue/Lobby) gitter chat.
- File issues at [https://github.com/formapro/psb-enqueue-producer/issues](https://github.com/formapro/psb-enqueue-producer/issues).

## Developed by Forma-Pro

Forma-Pro is a full stack development company which interests also spread to open source development. 
Being a team of strong professionals we have an aim an ability to help community by developing cutting edge solutions in the areas of e-commerce, docker & microservice oriented architecture where we have accumulated a huge many-years experience. 
Our main specialization is Symfony framework based solution, but we are always looking to the technologies that allow us to do our job the best way. We are committed to creating solutions that revolutionize the way how things are developed in aspects of architecture & scalability.

If you have any questions and inquires about our open source development, this product particularly or any other matter feel free to contact at opensource@forma-pro.com

## License

It is released under the [MIT License](LICENSE).

