<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\Events2Lrs\Event\Services\Tracking;

use ILIAS\Plugin\Events2Lrs\Event\EventHandler;

/**
 * Class AfterChangeEvent
 *
 * @package ILIAS\Plugin\Events2Lrs\Event
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */
class ReadCounterChange extends EventHandler
{
    public function __construct(int $queueId)
    {
        $this->event = 'readCounterChange'; # lcfirst(__CLASS__);
        // add code
        #echo '<pre>' . __CLASS__; var_dump($param); exit;
        #if(array_key_exists('spent_seconds', $param)) {
/*
        $param['input'] = [
            (int)$param['ref_id'],
            (int)$param['obj_id'],
            (int)$param['usr_id'],
            (string)$param['event'],
            (string)$param['date'],
            json_encode($param) // parameter
        ];
*/
        parent::__construct($queueId);

        #$this->dic->logger()->root()->dump(__CLASS__);
        #}
    }
}