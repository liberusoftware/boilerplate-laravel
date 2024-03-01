<p align="center">
 <img src="https://user-images.githubusercontent.com/796136/50286124-6f7f3780-046f-11e9-9f45-e8fedd4f786d.png" height="75px" alt="RoadRunner">
</p>
<p align="center">
 <a href="https://packagist.org/packages/spiral/roadrunner"><img src="https://poser.pugx.org/spiral/roadrunner/version"></a>
	<a href="https://pkg.go.dev/github.com/spiral/roadrunner?tab=doc"><img src="https://godoc.org/github.com/spiral/roadrunner?status.svg"></a>
	<a href="https://github.com/spiral/roadrunner/actions"><img src="https://github.com/spiral/roadrunner/workflows/CI/badge.svg" alt=""></a>
	<a href="https://goreportcard.com/report/github.com/spiral/roadrunner"><img src="https://goreportcard.com/badge/github.com/spiral/roadrunner"></a>
	<a href="https://scrutinizer-ci.com/g/spiral/roadrunner/?branch=master"><img src="https://scrutinizer-ci.com/g/spiral/roadrunner/badges/quality-score.png"></a>
	<a href="https://discord.gg/spiralphp"><img src="https://img.shields.io/badge/discord-chat-magenta.svg"></a>
	<a href="https://packagist.org/packages/spiral/roadrunner"><img src="https://img.shields.io/packagist/dd/spiral/roadrunner?style=flat-square"></a>
</p>

RoadRunner is an open-source (MIT licensed) high-performance PHP application server, load balancer, and process manager.
It supports running as a service with the ability to extend its functionality on a per-project basis.

RoadRunner includes PSR-7/PSR-17 compatible HTTP and HTTP/2 server and can be used to replace classic Nginx+FPM setup
with much greater performance and flexibility.

<p align="center">
	<a href="https://roadrunner.dev/"><b>Official Website</b></a> | 
	<a href="https://docs.roadrunner.dev"><b>Documentation</b></a>
</p>


## Repository:

This repository contains the codebase PSR-7 PHP workers.
Check [spiral/roadrunner](https://github.com/spiral/roadrunner) to get application server.


## Installation:

To install application server and HTTP codebase:

```bash
composer require spiral/roadrunner-http nyholm/psr7
```

You can use the convenient installer to download the latest available compatible version of RoadRunner assembly:

```bash
composer require spiral/roadrunner-cli --dev
```

To download latest version of application server:

```bash
vendor/bin/rr get
```

> You can use any [PSR-17 compatible implementation](https://packagist.org/providers/psr/http-factory-implementation).


## Example:

To init abstract RoadRunner worker:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Nyholm\Psr7\Response;
use Nyholm\Psr7\Factory\Psr17Factory;

use Spiral\RoadRunner\Worker;
use Spiral\RoadRunner\Http\PSR7Worker;


// Create new RoadRunner worker from global environment
$worker = Worker::create();

// Create common PSR-17 HTTP factory
$factory = new Psr17Factory();

//
// Create PSR-7 worker and pass:
//  - RoadRunner worker
//  - PSR-17 ServerRequestFactory
//  - PSR-17 StreamFactory
//  - PSR-17 UploadFilesFactory
//
$psr7 = new PSR7Worker($worker, $factory, $factory, $factory);

while (true) {
    try {
        $request = $psr7->waitRequest();
    } catch (\Throwable $e) {
        // Although the PSR-17 specification clearly states that there can be
        // no exceptions when creating a request, however, some implementations
        // may violate this rule. Therefore, it is recommended to process the 
        // incoming request for errors.
        //
        // Send "Bad Request" response.
        $psr7->respond(new Response(400));
        continue;
    }

    try {
        // Here is where the call to your application code will be located. 
        // For example:
        //
        //  $response = $app->send($request);
        //
        // Reply by the 200 OK response
        $psr7->respond(new Response(200, [], 'Hello RoadRunner!'));
    } catch (\Throwable $e) {
        // In case of any exceptions in the application code, you should handle
        // them and inform the client about the presence of a server error.
        //
        // Reply by the 500 Internal Server Error response
        $psr7->respond(new Response(500, [], 'Something Went Wrong!'));

        // Additionally, we can inform the RoadRunner that the processing 
        // of the request failed.
        $worker->error((string)$e);
    }
}
```

### Stream response

To send a response in a stream, set the `$chunkSize` property in `PSR7Worker`:

```php
$psr7 = new PSR7Worker($worker, $factory, $factory, $factory);
$psr7->chunkSize = 512 * 1024; // 512KB
```

Now PSR7Worker will cut the response into chunks of 512KB and send them to the stream.

### Early hints

To send multiple responses you may use the `\Spiral\RoadRunner\Http\HttpWorker::respond()` method with
the `endOfStream` parameter set to `false`. This will send the response to the client and allow you to send
additional responses.

```php
/** @var \Spiral\RoadRunner\Http\PSR7Worker $psr7 */
$httpWorker = $psr7->getHttpWorker()
    ->respond(103, header: ['Link' => ['</style.css>; rel=preload; as=style']], endOfStream: false);

// End of stream will be sent automatically after PSR7Worker::respond() call
$psr7->respond(new Response(200, [], 'Hello RoadRunner!'));
```


[![try Spiral Framework](https://user-images.githubusercontent.com/773481/220979012-e67b74b5-3db1-41b7-bdb0-8a042587dedc.jpg)](https://spiral.dev/)

## Testing:

This codebase is automatically tested via host repository - [spiral/roadrunner](https://github.com/roadrunner-server/roadrunner).


## License:

The MIT License (MIT). Please see [`LICENSE`](./LICENSE) for more information. Maintained
by [Spiral Scout](https://spiralscout.com).
