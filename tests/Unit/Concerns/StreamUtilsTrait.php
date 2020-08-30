<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Tests\Goridge\Unit\Concerns;

trait StreamUtilsTrait
{
    /**
     * @var array
     */
    private $streams = [];

    /**
     * @param string $text
     * @return resource
     */
    protected function textToStream(string $text)
    {
        $this->streams[] = $memory = \fopen('php://memory', 'xb+');

        \fwrite($memory, $text);
        \fseek($memory, 0);

        return $memory;
    }

    /**
     * @return void
     */
    protected function tearDownStreamUtilsTrait(): void
    {
        foreach ($this->streams as $stream) {
            \fclose($stream);
        }
    }
}
