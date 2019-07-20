<?php

namespace Scaleplan\Notify\Exceptions;

/**
 * Class StructureException
 *
 * @package Scaleplan\Notify\Exceptions
 */
class StructureException extends \Exception
{
    public const MESSAGE = 'Structure error.';
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
