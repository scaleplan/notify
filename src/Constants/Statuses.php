<?php

namespace Scaleplan\Notify\Constants;

/**
 * Class Statuses
 *
 * @package Scaleplan\Notify\Constants
 */
final class Statuses
{
    public const OK        = 'ok';
    public const ATTENTION = 'attention';
    public const ALARM     = 'alarm';

    public const ALL = [
        self::OK,
        self::ATTENTION,
        self::ALARM,
    ];
}
