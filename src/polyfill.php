<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Spiral\Goridge\Exception;

class_alias(Exception\GoridgeException::class, \Spiral\Goridge\Exceptions\GoridgeException::class);
class_alias(Exception\InvalidArgumentException::class, \Spiral\Goridge\Exceptions\InvalidArgumentException::class);
class_alias(Exception\PrefixException::class, \Spiral\Goridge\Exceptions\PrefixException::class);
class_alias(Exception\RelayException::class, \Spiral\Goridge\Exceptions\RelayException::class);
class_alias(Exception\RelayFactoryException::class, \Spiral\Goridge\Exceptions\RelayFactoryException::class);
class_alias(Exception\RPCException::class, \Spiral\Goridge\Exceptions\RPCException::class);
class_alias(Exception\ServiceException::class, \Spiral\Goridge\Exceptions\ServiceException::class);
class_alias(Exception\TransportException::class, \Spiral\Goridge\Exceptions\TransportException::class);
