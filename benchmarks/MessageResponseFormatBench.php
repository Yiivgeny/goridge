<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Bench\Goridge;

use PhpBench\Benchmark\Metadata\Annotations\Revs;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;

/**
 * @BeforeMethods({"init"})
 */
class MessageResponseFormatBench
{
    public function init(): void
    {
        require __DIR__ . '/Message.php';
    }

    /**
     * @Revs(10000)
     * @Iterations(10)
     */
    public function benchResponseAsArray(): void
    {
        [$body, $flags] = $this->responseAsArray();

        \ob_start();
        echo $body;
        echo $flags;
        \ob_end_clean();
    }

    /**
     * @Revs(10000)
     * @Iterations(10)
     */
    public function benchResponseAsDTO(): void
    {
        $response = $this->responseAsDTO();

        \ob_start();
        echo $response->body;
        echo $response->flags;
        \ob_end_clean();
    }

    /**
     * @Revs(10000)
     * @Iterations(10)
     */
    public function benchResponseByReference(): void
    {
        $flags = null;
        $body = $this->responseByReference($flags);

        \ob_start();
        echo $body;
        echo $flags;
        \ob_end_clean();
    }

    private function responseAsArray(): array
    {
        return [
            \random_bytes(1024),
            \random_int(0, 1024)
        ];
    }

    private function responseAsDTO(): Message
    {
        $message = new Message();
        $message->body = \random_bytes(1024);
        $message->flags = \random_int(0, 1024);

        return $message;
    }

    private function responseByReference(?int &$flags = null): string
    {
        $flags = \random_int(0, 1024);

        return \random_bytes(1024);
    }
}
