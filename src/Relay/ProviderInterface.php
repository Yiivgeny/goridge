<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge\Relay;

use Spiral\Goridge\RelayInterface;

interface ProviderInterface
{
    /**
     * @param string $protocol
     * @return bool
     */
    public function match(string $protocol): bool;

    /**
     * @param string $protocol
     * @param string $signature
     * @return RelayInterface
     */
    public function create(string $protocol, string $signature): RelayInterface;
}
