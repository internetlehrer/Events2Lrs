<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\Events2Lrs\Event\Modules\Test;

use ILIAS\Plugin\Events2Lrs\Event\EventHandler;

/**
 * Class SuspendTestPass
 *
 * @package ILIAS\Plugin\Events2Lrs\Event
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */
class SuspendTestPass extends EventHandler
{
    public function __construct(int $queueId)
    {
        #$this->event = 'startTestPass';
        // add code

        parent::__construct($queueId);

    }
}