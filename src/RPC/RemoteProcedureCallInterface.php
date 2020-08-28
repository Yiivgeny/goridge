<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge\RPC;

use Spiral\Goridge\Relay\Payload;

interface RemoteProcedureCallInterface
{
    /**
     * @param string $method
     * @param mixed $payload
     * @param int $flags
     * @return mixed
     */
    public function call(string $method, $payload, int $flags = Payload::TYPE_JSON);
}
