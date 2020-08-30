<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Tests\Goridge\Unit\Relay\Protocol;

use Spiral\Goridge\Exception\TransportException;
use Spiral\Goridge\Relay\Protocol\DecoderInterface;
use Spiral\Goridge\Relay\Protocol\EncoderInterface;
use Spiral\Goridge\Relay\Protocol\GoridgeV2;
use Spiral\Goridge\Relay\Protocol\Protocol;
use Spiral\Tests\Goridge\Unit\Concerns\FakerTrait;

class GoridgeV2TestCase extends ProtocolTestCase
{
    use FakerTrait;

    /**
     * @return array
     * @throws \Exception
     */
    public function dataProvider(): array
    {
        return $this->generateDataProvider(function (int $chunk, int $size, int $flags): array {
            return [
                // Encoder
                $this->encoder($chunk),

                // Encoded message text
                \pack('CPJ', $flags, $size, $size) . ($message = $this->fakeText($size)),

                // Expected DTO
                new Expected($message, $flags, $size + GoridgeV2::HEADER_SIZE),
            ];
        });
    }

    /**
     * @param int $chunkSize
     * @return EncoderInterface
     */
    protected function encoder(int $chunkSize): EncoderInterface
    {
        return new GoridgeV2($chunkSize);
    }

    /**
     * @param int $chunkSize
     * @return DecoderInterface
     */
    protected function decoder(int $chunkSize): DecoderInterface
    {
        return new GoridgeV2($chunkSize);
    }

    /**
     * @return void
     */
    public function testDecodingInvalidChecksum(): void
    {
        $this->expectException(TransportException::class);

        Protocol::decodeThrough($this->decoder(512), static function () {
            return \pack('CPJ', 0, 42, 23);
        });
    }

    /**
     * @return void
     */
    public function testDecodingEmptyBody(): void
    {
        $message = Protocol::decodeThrough($this->decoder(512), static function () {
            return \pack('CPJ', 0, 0, 0);
        });

        $this->assertSame(0, $message->flags);
        $this->assertSame('', $message->body);
    }
}
