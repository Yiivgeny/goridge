<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge\Relay;

interface PayloadInterface
{
    /**
     * Must be set when no data to be sent.
     */
    public const PAYLOAD_NONE    = 2;

    /**
     * Must be set when data binary data.
     */
    public const PAYLOAD_RAW     = 4;

    /**
     * Must be set when data is error string or structure.
     */
    public const PAYLOAD_ERROR   = 8;

    /**
     * Defines that associated data must be treated as control data.
     */
    public const PAYLOAD_CONTROL = 16;
}
