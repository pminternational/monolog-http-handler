[![Build Status](https://travis-ci.org/msschl/monolog-http-handler.svg?branch=master)](https://travis-ci.org/msschl/monolog-http-handler)
[![Coverage Status](https://coveralls.io/repos/github/msschl/monolog-http-handler/badge.svg?branch=master)](https://coveralls.io/github/msschl/monolog-http-handler?branch=master)

# Monolog Http Handler

This package provides a HttpHandler for the [Monolog](https://github.com/Seldaek/monolog) library.

Prerequisites
-------------

- PHP 7.0 or above.
- Since this package adds a HttpHandler to the [Monolog](https://github.com/Seldaek/monolog) library, you should first install [Monolog](https://github.com/Seldaek/monolog#installation).

Installation
------------

Install the latest version with

```bash
$ composer require msschl/monolog-http-handler
```

#### After the installation

...you need to decide on which [HTTP client/adapter](https://packagist.org/providers/php-http/client-implementation) you want to use.

##### HTTP Clients

In order to send HTTP requests, you need a HTTP adapter. This package relies on HTTPlug which is build on top of [PSR-7](https://www.php-fig.org/psr/psr-7/)
and defines how HTTP message should be sent and received. You can use any library to send HTTP messages that
implements [php-http/client-implementation](https://packagist.org/providers/php-http/client-implementation).

Here is a list of all officially supported clients and adapters by HTTPlug: http://docs.php-http.org/en/latest/clients.html

Read more about HTTPlug in [their docs](http://docs.php-http.org/en/latest/httplug/users.html).

Basic Usage
-----------

```php
<?php

use Monolog\Logger;
use Msschl\Monolog\Handler\HttpHandler;

// create a log channel
$log = new Logger('name');

// push the HttpHandler to the monolog logger.
$log->pushHandler(new HttpHandler([
    'uri'     => 'https://localhost/your/endpoint/for/logging',
    'method'  => 'POST',
]));

// add records to the log
$log->warning('Foo');
$log->error('Bar');
```

About
-----

### Submitting bugs and feature requests

Bugs and feature request are tracked on [GitHub](https://github.com/msschl/monolog-http-handler/issues)

### Contributing

First of all, **thank you** for contributing!
In order to make code reviews easier please follow some simple rules listed in the [CONTRIBUTING.md](CONTRIBUTING.md) file.

License
-------

This project is licensed under the terms of the MIT license.
See the [LICENSE](LICENSE.md) file for license rights and limitations.
