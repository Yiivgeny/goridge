<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge\Relay\Protocol;

use Spiral\Goridge\Relay\Payload;

class DecodedMessage extends Message implements DecodedMessageInterface
{
    /**
     * @var int
     */
    public $flags;

    /**
     * @param string $body
     * @param int $flags
     */
    public function __construct(string $body, int $flags = Payload::TYPE_JSON)
    {
        $this->flags = $flags;

        parent::__construct($body);
    }
}
