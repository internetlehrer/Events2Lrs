<?php

namespace ILIAS\Plugin\Events2Lrs\Statement;


use ilCmiXapiLrsType;
use ILIAS\Plugin\Events2Lrs\Xapi\Statement\XapiStatement;


abstract class AbstractStatement extends XapiStatement
{
    public function __construct(
        ilCmiXapiLrsType $lrsType,
        array $eventParam = []
    )
    {
        parent::__construct($lrsType, $eventParam);
    }

}