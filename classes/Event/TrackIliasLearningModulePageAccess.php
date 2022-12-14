<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\Events2Lrs\Event\Services\Tracking;

use ILIAS\Plugin\Events2Lrs\Event\EventHandler;

/**
 * Class TrackIliasLearningModulePageAccess
 *
 * @package ILIAS\Plugin\Events2Lrs\Event
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */
class TrackIliasLearningModulePageAccess extends EventHandler
{
    public function __construct(int $queueId)
    {
        $this->event = 'trackIliasLearningModulePageAccess'; # lcfirst(__CLASS__);
        // add code

        parent::__construct($queueId);

    }
}