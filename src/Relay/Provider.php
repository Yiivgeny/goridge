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

abstract class Provider implements ProviderInterface
{
    /**
     * @var string
     */
    protected const ERROR_INVALID_FORMAT = 'Malformed connection format "%s://%s"';

    /**
     * @param string $protocol
     * @param string $signature
     * @return RelayFactoryException
     */
    protected function formatException(string $protocol, string $signature): RelayFactoryException
    {
        $message = \sprintf(self::ERROR_INVALID_FORMAT, $protocol, $signature);

        return new RelayFactoryException($message, 0x03);
    }
}
