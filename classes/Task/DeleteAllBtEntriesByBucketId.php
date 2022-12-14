<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\Events2Lrs\Task;

use ILIAS\DI\Container;


/**
 * Class DeleteAllBtEntriesByBucketId
 * @package ILIAS\Plugin\Events2Lrs\Task
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */
class DeleteAllBtEntriesByBucketId
{
    /**
     * @var Container
     */
    protected $dic;

    use \ILIAS\Plugin\Events2Lrs\Model\DbEvents2LrsQueue;

    public function __construct(int $queueId)
    {
        global $DIC;
        /**@var Container $DIC */

        $this->dic = $DIC;

        usleep(10);

        $this->setQueueIdAndLoadEntry($queueId);

        #$this->dic->logger()->root()->log('[EVENT TASK] ################## [EVENT TASK] deleteAllBtEntriesByBucketId ');
        $this->deleteAllBtEntriesByBucketId($this->parameter['bucket_id']);

        #$this->dic->logger()->root()->log('[EVENT TASK] ################## [EVENT TASK] deleteQueueEntryById() ');
        $this->deleteQueueEntryById();
    }

}