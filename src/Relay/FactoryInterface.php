<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge\Relay;

use Spiral\Goridge\Exception\RelayFactoryException;
use Spiral\Goridge\RelayInterface;

interface FactoryInterface
{
    /**
     * @param string $connection
     * @return RelayInterface
     * @throws RelayFactoryException
     */
    public function create(string $connection): RelayInterface;
}
