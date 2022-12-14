<?php
namespace ILIAS\Plugin\Events2Lrs\Statement;

use ilCmiXapiDateTime;
use ilCmiXapiLrsType;
use ILIAS\Plugin\Events2Lrs\Xapi\Statement\XapiStatement;
use ilObject;
use ilObjUser;

class UpdateStatus extends AbstractStatement
{
    public function __construct(ilCmiXapiLrsType $lrsType, array $eventParam = [])
    {

        $eventParam['event'] = 'updateStatus';

        parent::__construct($lrsType, $eventParam);
    }


    public function buildResult(): ?array
    {
        return $this->percentage === null ? null : [
            'score' => [
                'scaled' => $this->getScore(),
                'raw' =>$this->getScore(),
                'min' => 0,
                'max' => 1,
            ]
        ];
    }
}
