# Aplí Http Emitter

The available emitter implementations are:

    - `Apli\Http\Emitter\SapiEmitter`
    - `Apli\Http\Emitter\SapiStreamEmitter`
    - `Apli\Http\Emitter\SwooleEmitter`
    
> **Note:** [Swoole](https://www.swoole.co.uk/) is an async programming Framework for PHP that can be used to create high performance HTTP server applications, e.g. web APIs.

Installation
------------

```bash
composer require apli/http-emitter
```

Use
------------

How to use the SapiEmitter:

```php
<?php

use Apli\Http\Emitter\SapiEmitter;

$response = new \Response();
$response->getBody()->write("some content\n");

$emitter = new SapiEmitter();
$emitter->emit($response);
```

How to use the SwooleEmitter:

```php
<?php

use Apli\Http\Emitter\SwooleEmitter;
use Swoole\Http\Server;

$http = new Server('127.0.0.1', 9501);
 
$http->on('start', function ($server) {
    echo 'Swoole http server is started at http://127.0.0.1:9501';
});
 
$http->on("request", function ($request, $response) use ($app) {
    $psr7Response = new \Response();
    $psr7Response->getBody()->write("some content\n");
 
    $emitter = new SwooleEmitter($response);
    $emitter->emit($psr7Response);
});
 
$http->start();
```

If you missing the ```Content-Length``` header you can use the `\Narrowspark\HttpEmitter\Util\Util::injectContentLength` static method.

```php
<?php

use Apli\Http\Emitter\Util;

$response = new \Response();

$response = Util::injectContentLength($response);
``` 
