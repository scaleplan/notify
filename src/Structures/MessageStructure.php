<?php

namespace Scaleplan\Notify\Structures;

use Scaleplan\Notify\Constants\Statuses;
use Scaleplan\Notify\Exceptions\StructureException;
use function Scaleplan\Translator\translate;

/**
 * Class MessageStructure
 *
 * @package Scaleplan\Notify\Structures
 */
class MessageStructure extends AbstractStructure
{
    /**
     * @var string
     */
    protected $status;

    /**
     * @return string
     */
    public function getStatus() : string
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @throws StructureException
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
     */
    public function setStatus(string $status) : void
    {
        if (!\in_array($status, Statuses::ALL, true)) {
            throw new StructureException(translate('notify.status-not-found', ['status' => $status,]));
        }

        $this->status = $status;
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        return array_merge(parent::toArray(), ['status' => $this->status]);
    }
}
