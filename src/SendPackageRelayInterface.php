<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge;

/**
 * @deprecated since 2.5 and will be removed in 3.0
 */
interface SendPackageRelayInterface
{
    /**
     * Send message package with header and body.
     *
     * @deprecated since 2.5 and will be removed in 3.0. Please use {@see RelayInterface::batch()} method instead.
     * @param string $header
     * @param int|null $headerFlags
     * @param string $body
     * @param int|null $bodyFlags
     * @return void
     */
    public function sendPackage(string $header, ?int $headerFlags, string $body, ?int $bodyFlags = null): void;
}
