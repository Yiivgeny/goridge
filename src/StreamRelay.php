<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge;

use Spiral\Goridge\Exception\InvalidArgumentException;
use Spiral\Goridge\Exception\PrefixException;
use Spiral\Goridge\Exception\TransportException;

class StreamRelay extends Relay
{
    /**
     * @var string
     */
    private const ERROR_INVALID_RESOURCE_TYPE = '%s must be a valid resource type, but %s given';

    /**
     * @var string
     */
    private const ERROR_NOT_READABLE = 'Input resource stream must be readable';

    /**
     * @var string
     */
    private const ERROR_NOT_WRITABLE = 'Output resource stream must be readable';

    /**
     * Input stream resource.
     *
     * @var resource
     */
    private $in;

    /**
     * Output stream resource.
     *
     * @var resource
     */
    private $out;

    /**
     * Example:
     *
     * <code>
     *  $relay = new StreamRelay(STDIN, STDOUT);
     * </code>
     *
     * @param resource $in Must be readable.
     * @param resource $out Must be writable.
     *
     * @throws InvalidArgumentException
     */
    public function __construct($in, $out)
    {
        $this->assertStreamResource($in, 'Input stream');
        $this->assertResourceIsReadable($in);

        $this->assertStreamResource($out, 'Output stream');
        $this->assertResourceIsWritable($out);

        [$this->in, $this->out] = [$in, $out];

        parent::__construct();
    }

    /**
     * Checks that the resource is readable and throws an exception otherwise.
     *
     * @param resource $resource
     */
    private function assertResourceIsReadable($resource): void
    {
        if (! $this->isReadable($resource)) {
            throw new InvalidArgumentException(self::ERROR_NOT_READABLE, 0x03);
        }
    }

    /**
     * Checks that the resource is writable and throws an exception otherwise.
     *
     * @param resource $resource
     */
    private function assertResourceIsWritable($resource): void
    {
        if (! $this->isWritable($resource)) {
            throw new InvalidArgumentException(self::ERROR_NOT_WRITABLE, 0x04);
        }
    }

    /**
     * Checks that the resource is a valid stream resource.
     *
     * @param resource|mixed $resource
     * @param string $expected
     */
    private function assertStreamResource($resource, string $expected): void
    {
        if (\is_resource($resource)) {
            return;
        }

        $message = \sprintf(self::ERROR_INVALID_RESOURCE_TYPE, $expected, \get_debug_type($resource));

        try {
            //
            // Note: get_resource_type() can throw the TypeError in the case
            // that an invalid argument is passed (for example, an array)
            //
            $type = @\get_resource_type($resource);

            if (\is_string($type) && $type === 'stream') {
                return;
            }
        } catch (\TypeError $e) {
            throw new InvalidArgumentException($message, 0x01, $e);
        }

        throw new InvalidArgumentException($message, 0x02);
    }

    /**
     * Checks if stream is readable.
     *
     * @param resource $stream
     * @return bool
     */
    private function isReadable($stream): bool
    {
        if (! \is_resource($stream)) {
            return false;
        }

        $mode = \stream_get_meta_data($stream)['mode'];

        return (
            \strpos($mode, 'r') !== false ||
            \strpos($mode, '+') !== false
        );
    }

    /**
     * Checks if stream is writable.
     *
     * @param resource $stream
     * @return bool
     */
    private function isWritable($stream): bool
    {
        if (! \is_resource($stream)) {
            return false;
        }

        $mode = \stream_get_meta_data($stream)['mode'];

        return (
            \strpos($mode, 'x') !== false ||
            \strpos($mode, 'w') !== false ||
            \strpos($mode, 'c') !== false ||
            \strpos($mode, 'a') !== false ||
            \strpos($mode, '+') !== false
        );
    }

    /**
     * @param resource $stream
     * @return string
     */
    private function getUriString($stream): string
    {
        $meta = \stream_get_meta_data($stream);

        return \str_replace('php://', '', $meta);
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return \sprintf('pipes://%s:%s', $this->getUriString($this->in), $this->getUriString($this->out));
    }

    /**
     * @param string $body
     * @param int $length
     */
    protected function write(string $body, int $length): void
    {
        $status = @\fwrite($this->out, $body, $length);

        if ($status === false) {
            throw new TransportException('Unable to write payload to the resource stream');
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function read(int $length): string
    {
        $body = @\fread($this->in, $length);

        if ($body === false || $length !== \strlen($body)) {
            throw new PrefixException('Unable to read from resource stream');
        }

        return $body;
    }
}
