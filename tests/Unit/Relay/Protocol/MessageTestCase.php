<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Tests\Goridge\Unit\Relay\Protocol;

use Spiral\Goridge\Relay\Protocol\DecodedMessage;
use Spiral\Goridge\Relay\Protocol\EncodedMessage;
use Spiral\Tests\Goridge\Unit\Concerns\FakerTrait;
use Spiral\Tests\Goridge\Unit\TestCase;

class MessageTestCase extends TestCase
{
    use FakerTrait;

    /**
     * @return void
     */
    public function testDecodedMessageBody(): void
    {
        $message = new DecodedMessage($expected = $this->faker->text);

        $this->assertSame($expected, $message->body);
    }

    /**
     * @return void
     */
    public function testDecodedMessageFlags(): void
    {
        $message = new DecodedMessage('', $expected = $this->faker->randomDigit);

        $this->assertSame($expected, $message->flags);
    }

    /**
     * @return void
     */
    public function testDecodedMessageSerialization(): void
    {
        $message = new DecodedMessage($expected = $this->faker->text);

        $this->assertSame($expected, (string)$message);
    }

    /**
     * @return void
     */
    public function testEncodedMessageBody(): void
    {
        $message = new EncodedMessage($expected = $this->faker->text);

        $this->assertSame($expected, $message->body);
    }

    /**
     * @return void
     */
    public function testEncodedMessageSize(): void
    {
        $message = new EncodedMessage('', $expected = $this->faker->randomDigit);

        $this->assertSame($expected, $message->size);
    }

    /**
     * @return void
     */
    public function testEncodedMessageSerialization(): void
    {
        $message = new EncodedMessage($expected = $this->faker->text);

        $this->assertSame($expected, (string)$message);
    }
}
