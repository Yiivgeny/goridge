<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge\Relay;

use Spiral\Goridge\Exception\RelayFactoryException;
use Spiral\Goridge\RelayInterface;
use Spiral\Goridge\StreamRelay;

class StreamProvider extends Provider
{
    /**
     * An alias of {@see StreamProvider::PROTOCOL_PHP} naming.
     *
     * @var string
     */
    public const PROTOCOL_PIPES = 'pipes';

    /**
     * @var string
     */
    public const PROTOCOL_PHP = 'php';

    /**
     * @var string
     */
    private const ERROR_BAD_STREAM = 'Could not open "%s" stream %s';

    /**
     * @var string
     */
    private const OPEN_MODE_IN = 'rb';

    /**
     * @var string
     */
    private const OPEN_MODE_OUT = 'wb';

    /**
     * @var string
     */
    private const SIGNATURE_DELIMITER = ':';

    /**
     * {@inheritDoc}
     */
    public function match(string $protocol): bool
    {
        return \in_array($protocol, [self::PROTOCOL_PHP, self::PROTOCOL_PIPES], true);
    }

    /**
     * {@inheritDoc}
     */
    public function create(string $protocol, string $signature): RelayInterface
    {
        [$input, $output] = $this->parseSignature($protocol, $signature);

        return new StreamRelay(
            $this->open($input, self::OPEN_MODE_IN),
            $this->open($output, self::OPEN_MODE_OUT)
        );
    }

    /**
     * @param string $protocol
     * @param string $signature
     * @return array|string[]
     */
    private function parseSignature(string $protocol, string $signature): array
    {
        $parts = \array_filter(
            \explode(self::SIGNATURE_DELIMITER, $signature)
        );

        if (\count($parts) !== 2) {
            throw $this->formatException($protocol, $signature);
        }

        return $parts;
    }

    /**
     * @param string $pipe
     * @param string $mode
     * @return resource
     */
    private function open(string $pipe, string $mode)
    {
        \error_clear_last();

        $resource = @\fopen("php://$pipe", $mode);

        if ($resource === false || \error_get_last() !== null) {
            $message = \sprintf(self::ERROR_BAD_STREAM, $pipe, $this->getOpenModeDescription($mode));

            throw new RelayFactoryException($message);
        }

        return $resource;
    }

    /**
     * @param string $mode
     * @return string
     */
    private function getOpenModeDescription(string $mode): string
    {
        switch ($mode) {
            case self::OPEN_MODE_IN:
                return 'for reading';

            case self::OPEN_MODE_OUT:
                return 'for writing';

            default:
                return \sprintf('with "%s" mode', $mode);
        }
    }
}
