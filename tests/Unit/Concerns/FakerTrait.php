<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Tests\Goridge\Unit\Concerns;

use Faker\Factory;
use Faker\Generator;

trait FakerTrait
{
    /**
     * @var Generator
     */
    private $faker;

    /**
     * @return void
     */
    protected function setUpFakerTrait(): void
    {
        $this->faker();
    }

    /**
     * @return Generator
     */
    protected function faker(): Generator
    {
        return $this->faker ?? $this->faker = Factory::create();
    }

    /**
     * @param int $bytes
     * @return string
     * @throws \Exception
     */
    protected function fakeText(int $bytes): string
    {
        if ($bytes < 5) {
            return \random_bytes($bytes);
        }

        $text = $this->faker()->text($bytes);

        $suffix = $bytes - \strlen($text);

        return $text . ($suffix > 0 ? \random_bytes($suffix) : '');
    }
}

