<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\Events2Lrs\Event\Services\Tracking;

/**
 * Class SendAllStatements
 *
 * @package ILIAS\Plugin\Events2Lrs\Event
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */
class SendAllStatements
{
    public function __construct(int $queueId)
    {
        // (cronjob will trigger the event sendAllStatements that init this class)
        new \ILIAS\Plugin\Events2Lrs\Task\SendAllStatements($queueId);
    }


}