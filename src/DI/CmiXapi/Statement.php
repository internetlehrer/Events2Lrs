<?php

namespace ILIAS\Plugins\Events2Lrs\DI\CmiXapi;

use ilCmiXapiLrsType;
use ILIAS\Plugin\Events2Lrs\Xapi\Statement\XapiStatement;

class Statement extends XapiStatement
{
    public function __construct(ilCmiXapiLrsType $lrsType, array $param)
    {
        parent::__construct($lrsType, $param);
    }
}