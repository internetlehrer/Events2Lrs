<?php

namespace ILIAS\Plugin\Events2Lrs\Statement;


use ilCmiXapiLrsType;


class AfterChangeEvent extends AbstractStatement
{
    public function __construct(
        ilCmiXapiLrsType $lrsType,
        array $eventParam = []
    )
    {
        $eventParam['event'] = 'afterChangeEvent';

        parent::__construct($lrsType, $eventParam);
    }

    public function buildResult(): ?array
    {
        return [
            "duration" => "PT" . $this->eventParam['changeProp']['spent_seconds'] . "S"
        ];
    }
}