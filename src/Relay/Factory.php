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
use Spiral\Goridge\Relay\SocketRelay\SocketProvider;
use Spiral\Goridge\Relay\StreamRelay\StreamProvider;
use Spiral\Goridge\RelayInterface;

class Factory implements FactoryInterface
{
    /**
     * @var string
     */
    private const ERROR_INVALID_FORMAT = 'Malformed connection format "%s"';

    /**
     * @var string
     */
    private const ERROR_INVALID_PROTOCOL = 'Protocol "%s" is not registered';

    /**
     * @var string[]
     */
    private const DEFAULT_PROVIDERS = [
        StreamProvider::class,
        SocketProvider::class,
    ];

    /**
     * @var ProviderInterface[]
     */
    private $providers = [];

    /**
     * Factory constructor.
     */
    public function __construct()
    {
        $this->bootDefaultProviders();
    }

    /**
     * @return void
     */
    private function bootDefaultProviders(): void
    {
        foreach (self::DEFAULT_PROVIDERS as $matcher) {
            $this->add(new $matcher());
        }
    }

    /**
     * @param ProviderInterface $provider
     * @param bool $prepend
     */
    public function add(ProviderInterface $provider, bool $prepend = false): void
    {
        if ($prepend) {
            \array_unshift($this->providers, $provider);

            return;
        }

        $this->providers[] = $provider;
    }

    /**
     * @param callable $match
     */
    public function remove(callable $match): void
    {
        $filter = static function (ProviderInterface $matcher) use ($match): bool {
            return ! $match($matcher);
        };

        $this->providers = \array_filter($this->providers, $filter);
    }

    /**
     * @param string $provider
     */
    public function removeClass(string $provider): void
    {
        $this->remove(static function (ProviderInterface $haystack) use ($provider) {
            return \get_class($haystack) === $provider;
        });
    }

    /**
     * @param ProviderInterface $provider
     */
    public function removeInstance(ProviderInterface $provider): void
    {
        $this->remove(static function (ProviderInterface $haystack) use ($provider) {
            return $haystack === $provider;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function create(string $connection): RelayInterface
    {
        [$protocol, $signature] = $this->extract($connection);

        $matcher = $this->lookup($protocol);

        if ($matcher === null) {
            throw new RelayFactoryException(\sprintf(self::ERROR_INVALID_PROTOCOL, $protocol), 0x01);
        }

        return $matcher->create($protocol, $signature);
    }

    /**
     * @param string $connection
     * @return array
     */
    private function extract(string $connection): array
    {
        $chunks = \explode('://', $connection);

        if (\count($chunks) !== 2) {
            throw new RelayFactoryException(\sprintf(self::ERROR_INVALID_FORMAT, $connection), 0x02);
        }

        return $chunks;
    }

    /**
     * @param string $protocol
     * @return ProviderInterface|null
     */
    private function lookup(string $protocol): ?ProviderInterface
    {
        foreach ($this->providers as $matcher) {
            if ($matcher->match($protocol)) {
                return $matcher;
            }
        }

        return null;
    }
}
