<?php

declare(strict_types=1);

use Core\Foundation\Application;
use Core\Http\Kernel;
use Core\Http\Request;

define('LUFLY_START', microtime(true));

$app = require dirname(__DIR__) . '/bootstrap.php';

$kernel = $app->get(Kernel::class);
$response = $kernel->handle(Request::capture());
$response->send();
$kernel->terminate();

