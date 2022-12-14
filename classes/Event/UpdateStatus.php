<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\Events2Lrs\Event\Services\Tracking;

use ILIAS\Plugin\Events2Lrs\Event\EventHandler;

/**
 * Class UpdateStatus
 *
 * @package ILIAS\Plugin\Events2Lrs\Event
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */
class UpdateStatus extends EventHandler
{
    public function __construct(int $queueId)
    {
        $this->event = lcfirst('UpdateStatus');
        /*
        $param['input'] = [
            0,
            (int)$param['obj_id'],
            (int)$param['usr_id'],
            (string)$param['event'],
            (string)$param['date'],
            json_encode($param) // parameter
        ];
*/
        parent::__construct($queueId);
    }
}