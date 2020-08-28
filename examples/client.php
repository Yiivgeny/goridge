<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Spiral\Goridge\Relay;
use Spiral\Goridge\RPC;

/**
 * Require Composer autoloader script
 */
$vendor = \is_file(__DIR__ . '/vendor/autoload.php')
    ? __DIR__ . '/vendor/autoload.php'
    : __DIR__ . '/../vendor_php/autoload.php';

require $vendor;

/**
 * Creates a new RPC client
 */
$rpc = new RPC(Relay::create('tcp://127.0.0.1:6001'));

/**
 * Execute remote method ({@link ./server.go:15}):
 * <code>
 *  func (a *App) Hi(name string, r *string) error {
 *      *r = fmt.Sprintf("Hello, %s!", name)
 *      return nil
 *  }
 * </code>
 */
echo $rpc->call('App.Hi', 'Antony');
