<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Tests\Goridge\Unit;

trait InteractWithTraits
{
    /**
     * @param string $fqn
     * @return string
     */
    private function classBasename(string $fqn): string
    {
        return \basename(\str_replace('\\', \DIRECTORY_SEPARATOR, $fqn));
    }

    /**
     * @return void
     */
    protected function setUpTraits(): void
    {
        foreach (\class_uses($this) as $trait) {
            $method = 'setUp' . \ucfirst($this->classBasename($trait));

            if (\method_exists($this, $method)) {
                $this->$method();
            }
        }
    }

    /**
     * @return void
     */
    protected function tearDownTraits(): void
    {
        foreach (\class_uses($this) as $trait) {
            $method = 'tearDown' . \ucfirst($this->classBasename($trait));

            if (\method_exists($this, $method)) {
                $this->$method();
            }
        }
    }
}
