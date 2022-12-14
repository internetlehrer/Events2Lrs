<?php
namespace ILIAS\Plugin\Events2Lrs\Statement;

use ilCmiXapiDateTime;
use ilCmiXapiLrsType;
use ILIAS\Plugin\Events2Lrs\Xapi\Statement\XapiStatement;
use ilObject;
use ilObjUser;

class ReadCounterChange extends AbstractStatement
{
    public function __construct(ilCmiXapiLrsType $lrsType, array $eventParam = [])
    {

        $eventParam['event'] = 'readCounterChange';

        parent::__construct($lrsType, $eventParam);
    }

    public function buildResult(): ?array
    {
        return [
            "duration" => "PT" . $this->eventParam['changeProp']['spent_seconds'] . "S"
        ];
    }
}