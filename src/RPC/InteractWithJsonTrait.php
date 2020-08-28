<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge\RPC;

use Spiral\Goridge\Exception\ServiceException;

trait InteractWithJsonTrait
{
    /**
     * @var int
     */
    protected $jsonOptions = 0;

    /**
     * @var int
     */
    protected $jsonDepth = 512;

    /**
     * @var bool
     */
    protected $jsonDecodeAsArray = true;

    /**
     * @return void
     */
    protected function bootInteractWithJsonTrait(): void
    {
        if (\defined('JSON_THROW_ON_ERROR')) {
            $this->jsonOptions |= \JSON_THROW_ON_ERROR;
        }
    }

    /**
     * @param mixed $payload
     * @return string
     * @throws ServiceException
     */
    protected function jsonEncode($payload): string
    {
        try {
            $body = @\json_encode($payload, $this->jsonOptions, $this->jsonDepth);

            if (\json_last_error() !== \JSON_ERROR_NONE) {
                throw new \JsonException(\json_last_error_msg());
            }
        } catch (\Throwable $e) {
            throw new ServiceException($e->getMessage(), 0x01, $e);
        }

        return $body;
    }

    /**
     * @param string $json
     * @return mixed
     * @throws ServiceException
     */
    protected function jsonDecode(string $json)
    {
        try {
            $result = @\json_decode($json, $this->jsonDecodeAsArray, $this->jsonDepth, $this->jsonOptions);

            if (\json_last_error() !== \JSON_ERROR_NONE) {
                throw new \JsonException(\json_last_error_msg());
            }
        } catch (\Throwable $e) {
            throw new ServiceException($e->getMessage(), 0x02, $e);
        }

        return $result;
    }
}
