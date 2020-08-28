<?php

/**
 * This file is part of Goridge package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPSTORM_META {

    registerArgumentsSet('goridge_default_protocols', 'tcp://', 'unix://', 'pipes://', 'php://');

    expectedArguments(\Spiral\Goridge\Relay::create(), 0, argumentsSet('goridge_default_protocols'));
    expectedArguments(\Spiral\Goridge\Relay\Factory::create(), 0, argumentsSet('goridge_default_protocols'));

    registerArgumentsSet('goridge_payload_flags',
        \Spiral\Goridge\Relay\PayloadInterface::PAYLOAD_NONE |
        \Spiral\Goridge\Relay\PayloadInterface::PAYLOAD_RAW |
        \Spiral\Goridge\Relay\PayloadInterface::PAYLOAD_ERROR |
        \Spiral\Goridge\Relay\PayloadInterface::PAYLOAD_CONTROL
    );

    expectedArguments(\Spiral\Goridge\Relay::send(), 1, argumentsSet('goridge_payload_flags'));
    expectedArguments(\Spiral\Goridge\StreamRelay::send(), 1, argumentsSet('goridge_payload_flags'));
    expectedArguments(\Spiral\Goridge\SocketRelay::send(), 1, argumentsSet('goridge_payload_flags'));
    expectedArguments(\Spiral\Goridge\RelayInterface::send(), 1, argumentsSet('goridge_payload_flags'));

}

namespace Spiral\Goridge\Exceptions {

    use Spiral\Goridge\Exception;

    /**
     * @deprecated since 2.5 and will be removed in 3.0. Please use {@see Exception\GoridgeException} instead.
     */
    class GoridgeException extends Exception\GoridgeException {}

    /**
     * @deprecated since 2.5 and will be removed in 3.0. Please use {@see Exception\InvalidArgumentException} instead.
     */
    class InvalidArgumentException extends Exception\InvalidArgumentException {}

    /**
     * @deprecated since 2.5 and will be removed in 3.0. Please use {@see Exception\PrefixException} instead.
     */
    class PrefixException extends Exception\PrefixException {}

    /**
     * @deprecated since 2.5 and will be removed in 3.0. Please use {@see Exception\RelayException} instead.
     */
    class RelayException extends Exception\RelayException {}

    /**
     * @deprecated since 2.5 and will be removed in 3.0. Please use {@see Exception\RelayFactoryException} instead.
     */
    class RelayFactoryException extends Exception\RelayFactoryException {}

    /**
     * @deprecated since 2.5 and will be removed in 3.0. Please use {@see Exception\RPCException} instead.
     */
    class RPCException extends Exception\RPCException {}

    /**
     * @deprecated since 2.5 and will be removed in 3.0. Please use {@see Exception\ServiceException} instead.
     */
    class ServiceException extends Exception\ServiceException {}

    /**
     * @deprecated since 2.5 and will be removed in 3.0. Please use {@see Exception\TransportException} instead.
     */
    class TransportException extends Exception\TransportException {}

}
