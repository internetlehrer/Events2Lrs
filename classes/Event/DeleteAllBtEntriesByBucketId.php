<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\Events2Lrs\Event\Services\Tracking;

/**
 * Class DeleteAllBtEntriesByBucketId
 * @package ILIAS\Plugin\Events2Lrs\Event
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */
class DeleteAllBtEntriesByBucketId
{
    public function __construct(int $queueId)
    {
        // (backgroundTask will trigger the event deleteAllBtEntriesByBucketId that init this class)
        new \ILIAS\Plugin\Events2Lrs\Task\DeleteAllBtEntriesByBucketId($queueId);
    }

}