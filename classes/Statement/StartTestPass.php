<?php

namespace ILIAS\Plugin\Events2Lrs\Statement;


use ilCmiXapiLrsType;

class StartTestPass extends TestPass
{


    public function __construct(ilCmiXapiLrsType $lrsType, array $eventParam = [])
    {

        $eventParam['event'] = 'startTestPass';

        parent::__construct($lrsType, $eventParam);

    }

    public function buildResult(): ?array
    {
        return null;
    }

}