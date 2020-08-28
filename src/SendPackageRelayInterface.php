<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge;

interface SendPackageRelayInterface
{
    /**
     * Send message package with header and body.
     *
     * @param string   $headerPayload
     * @param int|null $headerFlags
     * @param string   $bodyPayload
     * @param int|null $bodyFlags
     * @return mixed
     */
    public function sendPackage(
        string $headerPayload,
        ?int $headerFlags,
        string $bodyPayload,
        ?int $bodyFlags = null
    );
}
