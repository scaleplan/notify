<?php

namespace Scaleplan\Notify\Structures;

/**
 * Class StructureFabric
 *
 * @package Scaleplan\Notify\Structures
 */
class StructureFabric
{
    /**
     * @param array $data
     *
     * @return AbstractStructure
     *
     * @throws \Scaleplan\Notify\Exceptions\StructureException
     */
    public static function getStructure(array $data) : AbstractStructure
    {
        $message = $data['message'] ?? null;
        if ($data['limits']) {
            $structure = new TimerStructure();
            $structure->setMessage($message);
            $startTime = $data['start_time'] ?? null;
            $structure->setStartTime($startTime);
            $structure->setLimits(new TimerLimitsStructure($startTime));
            $structure->setHref($data['href'] ?? null);
            $structure->setTitleHideOn($data['title_hide_on'] ?? null);
            $structure->setInitTime($data['init_time'] ?? null);

            return $structure;
        }

        $structure = new MessageStructure();
        $structure->setMessage($message);
        $structure->setStatus($data['status'] ?? null);

        return $structure;
    }
}
