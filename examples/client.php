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

require __DIR__ . '/vendor/autoload.php';

$factory = new Factory();

$rpc = new RPC(
    $factory->create('tcp://127.0.0.1:6001')
);

echo $rpc->call('App.Hi', 'Antony');
