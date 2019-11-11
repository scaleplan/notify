<?php

namespace Scaleplan\Notify\Exceptions;

/**
 * Class NotifyException
 *
 * @package Scaleplan\Notify\Exceptions
 */
class NotifyException extends \Exception
{
    public const MESSAGE = 'Notify error.';
    public const CODE = 500;

    /**
     * StructureException constructor.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct($message = '', $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message ?: static::MESSAGE, $code ?: static::CODE, $previous);
    }
}
