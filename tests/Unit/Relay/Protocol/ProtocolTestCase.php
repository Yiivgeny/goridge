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
use Spiral\Goridge\Relay\Payload;
use Spiral\Goridge\Relay\Protocol\DecoderInterface;
use Spiral\Goridge\Relay\Protocol\EncoderInterface;
use Spiral\Goridge\Relay\Protocol\Protocol;
use Spiral\Tests\Goridge\Unit\Concerns\StreamUtilsTrait;
use Spiral\Tests\Goridge\Unit\TestCase;

abstract class ProtocolTestCase extends TestCase
{
    use StreamUtilsTrait;

    /**
     * @return array
     */
    abstract public function dataProvider(): array;

    /**
     * @dataProvider dataProvider
     *
     * @param EncoderInterface $encoder
     * @param string $encoded
     * @param Expected $expected
     */
    public function testEncodingBody(EncoderInterface $encoder, string $encoded, Expected $expected): void
    {
        $actual = $encoder->encode($expected->body, $expected->flags);

        $this->assertSame($encoded, $actual->body);
    }

    /**
     * @dataProvider dataProvider
     *
     * @param EncoderInterface $encoder
     * @param string $encoded
     * @param Expected $expected
     */
    public function testEncodingSize(EncoderInterface $encoder, string $encoded, Expected $expected): void
    {
        $actual = $encoder->encode($expected->body, $expected->flags);

        $this->assertSame($expected->size, $actual->size);
    }

    /**
     * @return array
     */
    public function emptyFlagsDataProvider(): array
    {
        return [
            'Empty' => [Payload::TYPE_EMPTY],

            'Empty + Raw'     => [Payload::TYPE_EMPTY | Payload::TYPE_RAW],
            'Empty + Error'   => [Payload::TYPE_EMPTY | Payload::TYPE_ERROR],
            'Empty + Control' => [Payload::TYPE_EMPTY | Payload::TYPE_CONTROL],

            'Empty + Raw + Error'   => [Payload::TYPE_EMPTY | Payload::TYPE_RAW | Payload::TYPE_ERROR],
            'Empty + Raw + Control' => [Payload::TYPE_EMPTY | Payload::TYPE_RAW | Payload::TYPE_CONTROL],

            'Empty + Error + Control' => [Payload::TYPE_EMPTY | Payload::TYPE_ERROR | Payload::TYPE_CONTROL],
        ];
    }

    /**
     * @dataProvider emptyFlagsDataProvider
     *
     * @param int $flags
     */
    public function testEncodingNonEmptyBodyWithEmptyFlag(int $flags): void
    {
        $this->expectException(TransportException::class);

        $encoder = $this->encoder(1024);

        $encoder->encode('message', $flags);
    }

    /**
     * @param int $chunkSize
     * @return EncoderInterface
     */
    abstract protected function encoder(int $chunkSize): EncoderInterface;

    /**
     * @dataProvider dataProvider
     *
     * @param EncoderInterface $encoder
     * @param string $encoded
     * @param Expected $expected
     */
    public function testBatchEncodingBody(EncoderInterface $encoder, string $encoded, Expected $expected): void
    {
        $actual = Protocol::encodeBatch($encoder, [$expected->body => $expected->flags]);

        $this->assertSame($encoded, $actual->body);
    }

    /**
     * @dataProvider dataProvider
     *
     * @param EncoderInterface $encoder
     * @param string $encoded
     * @param Expected $expected
     */
    public function testBatchEncodingSize(EncoderInterface $encoder, string $encoded, Expected $expected): void
    {
        $actual = Protocol::encodeBatch($encoder, [$expected->body => $expected->flags]);

        $this->assertSame($expected->size, $actual->size);
    }

    /**
     * @dataProvider dataProvider
     *
     * @param DecoderInterface $decoder
     * @param string $encoded
     * @param Expected $expected
     */
    public function testDecodingBody(DecoderInterface $decoder, string $encoded, Expected $expected): void
    {
        [$stream, $memory] = [$decoder->decode(), $this->textToStream($encoded)];

        while ($stream->valid()) {
            $stream->send(\fread($memory, $stream->current()));
        }

        $this->assertSame($expected->body, $stream->getReturn()->body);
    }

    /**
     * @dataProvider dataProvider
     *
     * @param DecoderInterface $decoder
     * @param string $encoded
     * @param Expected $expected
     */
    public function testDecodingFlags(DecoderInterface $decoder, string $encoded, Expected $expected): void
    {
        [$stream, $memory] = [$decoder->decode(), $this->textToStream($encoded)];

        while ($stream->valid()) {
            $stream->send(\fread($memory, $stream->current()));
        }

        $this->assertSame($expected->flags, $stream->getReturn()->flags);
    }

    /**
     * @dataProvider dataProvider
     *
     * @param DecoderInterface $decoder
     * @param string $encoded
     * @param Expected $expected
     */
    public function testDecodingBodyThroughCallback(DecoderInterface $decoder, string $encoded, Expected $expected): void
    {
        $memory = $this->textToStream($encoded);

        $message = Protocol::decodeThrough($decoder, static function (int $size) use ($memory) {
            return \fread($memory, $size);
        });

        $this->assertSame($expected->body, $message->body);
    }

    /**
     * @dataProvider dataProvider
     *
     * @param DecoderInterface $decoder
     * @param string $encoded
     * @param Expected $expected
     */
    public function testDecodingFlagsThroughCallback(DecoderInterface $decoder, string $encoded, Expected $expected): void
    {
        $memory = $this->textToStream($encoded);

        $message = Protocol::decodeThrough($decoder, static function (int $size) use ($memory) {
            return \fread($memory, $size);
        });

        $this->assertSame($expected->flags, $message->flags);
    }

    /**
     * @return void
     */
    public function testDecodingInvalidHeader(): void
    {
        $this->expectException(TransportException::class);

        Protocol::decodeThrough($this->decoder(512), static function () {
            return 'OLOLOLOLO';
        });
    }

    /**
     * @param int $chunkSize
     * @return DecoderInterface
     */
    abstract protected function decoder(int $chunkSize): DecoderInterface;

    /**
     * @param \Closure $each
     * @return array
     */
    protected function generateDataProvider(\Closure $each): array
    {
        $result = [];

        foreach ($this->getChunkSizes() as $chunk) {
            foreach ($this->getMessageSizes() as $size) {
                foreach ($this->getMessageFlags() as $flag) {
                    $name = \sprintf('chunk (size): %d, message (size): %d, flags: %d', $chunk, $size, $flag);

                    $result[$name] = $each($chunk, $size, $flag);
                }
            }
        }

        return $result;
    }

    /**
     * @return int[]
     */
    protected function getChunkSizes(): array
    {
        return [1, 512, 8192, 65535 * 2];
    }

    /**
     * @return int[]
     */
    protected function getMessageSizes(): array
    {
        return [1, 512, 8192, 65535 * 2];
    }

    /**
     * @return int[]
     */
    protected function getMessageFlags(): array
    {
        return [0, 4, 8, 16, 4 | 8, 4 | 16, 8 | 16];
    }
}
