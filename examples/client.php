<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Spiral\Goridge\Relay\Factory;
use Spiral\Goridge\RPC;

require __DIR__ . '/../vendor_php/autoload.php';

/** @var \Spiral\Goridge\SocketRelay $transport */
$transport = (new Factory())->create('tcp://127.0.0.1:6001');

$transport->connect();
$transport->close();
$transport->connect();


$rpc = new RPC($transport);

$response = $rpc->call('App.Hi', 'Antony');

echo $response;
