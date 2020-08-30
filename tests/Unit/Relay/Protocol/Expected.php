<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Tests\Goridge\Unit\Relay\Protocol;

class Expected
{
    public $body;
    public $size;
    public $flags;

    public function __construct(string $body, int $flags, int $size = null)
    {
        $this->body = $body;
        $this->flags = $flags;
        $this->size = $size ?? \strlen($body);
    }
}
