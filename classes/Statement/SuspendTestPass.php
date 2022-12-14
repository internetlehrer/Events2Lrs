<?php

namespace ILIAS\Plugin\Events2Lrs\Statement;


use ilCmiXapiDateTime;
use ilCmiXapiLrsType;
use ILIAS\Plugin\Events2Lrs\Xapi\Statement\XapiStatement;
use ilObject;
use ilObjUser;


class SuspendTestPass extends TestPass
{
    public function __construct(ilCmiXapiLrsType $lrsType, array $eventParam = [])
    {

        $eventParam['event'] = 'suspendTestPass';

        parent::__construct($lrsType, $eventParam);
    }

}