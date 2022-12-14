<?php

namespace ILIAS\Plugin\Events2Lrs\Statement;


use ilCmiXapiDateTime;
use ilCmiXapiLrsType;
use ILIAS\Plugin\Events2Lrs\Xapi\Statement\XapiStatement;
use ilObject;
use ilObjTest;
use ilObjUser;


class FinishTestPass extends TestPass
{
    public function __construct(ilCmiXapiLrsType $lrsType, array $eventParam = [])
    {

        $eventParam['event'] = 'finishTestPass';

        parent::__construct($lrsType, $eventParam);

    }

    public function buildTimestamp() : string
    {
        /* Generate Timestamp */
        $raw_timestamp = $this->test_details['result_tstamp'];

        $timestamp = new ilCmiXapiDateTime($raw_timestamp, IL_CAL_UNIX);

        return $timestamp->toXapiTimestamp();

    }

    public function buildResult(): ?array
    {

        $result = parent::buildResult();

        $result['completion'] = true;

        $result['success'] = (bool)$this->test_details['passed'];

        return $result;

    }


}