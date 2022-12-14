<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\Events2Lrs\Event\Services\Tracking;

/**
 * Class SendStatementsByQueueId
 *
 * @package ILIAS\Plugin\Events2Lrs\Event
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */
class SendStatementsByQueueId
{
    public function __construct(array $queueIds)
    {
        // (cronjob will trigger the event sendAllStatements that init this class)
        new \ILIAS\Plugin\Events2Lrs\Task\SendStatementsByQueueId($queueIds);
    }


}