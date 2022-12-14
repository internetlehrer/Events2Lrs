<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\Events2Lrs\Event\Modules\Test;

use ILIAS\Plugin\Events2Lrs\Event\EventHandler;

/**
 * Class FinishTestResponse
 *
 * @package ILIAS\Plugin\Events2Lrs\Event
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */
class FinishTestResponse extends EventHandler
{
    public function __construct(int $queueId)
    {
        $this->event = 'finishTestResponse'; # lcfirst(__CLASS__);
        // add code

        parent::__construct($queueId);

    }
}