<?php

namespace ILIAS\Plugin\Events2Lrs\Statement;


use ilCmiXapiDateTime;
use ilCmiXapiLrsType;
use ILIAS\Plugin\Events2Lrs\Xapi\Statement\XapiStatement;
use ilObject;
use ilObjUser;


class ResumeTestPass extends TestPass
{
    public function __construct(ilCmiXapiLrsType $lrsType, array $eventParam = [])
    {
        $eventParam['event'] = 'resumeTestPass';

        parent::__construct($lrsType, $eventParam);
    }

}