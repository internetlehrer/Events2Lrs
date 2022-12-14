<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\Events2Lrs\Task;

use ILIAS\DI\Container;
use ILIAS\Plugin\Events2Lrs\Xapi\Request\XapiRequest;
use ilLoggerFactory;
use ilCmiXapiLrsType;

/**
 * Class HandleQueueEntries
 *
 * @package ILIAS\Plugin\Events2Lrs\Task
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */
class HandleQueueEntries
{
    /**
     * @var Container
     */
    protected $dic;

    /**
     * @var ilCmiXapiLrsType|null
     */
    protected $lrsType;

    /**
     * @var XapiRequest|null $lrsRequest
     */
    protected $lrsRequest;

    use \ILIAS\Plugin\Events2Lrs\Model\DbEvents2LrsQueue;

    public function __construct(int $queueId)
    {
        global $DIC; /**@var Container $DIC */

        $this->dic = $DIC;

        $this->setQueueIdAndLoadEntry($queueId);

        $this->bucketId = json_decode($this->parameter, 1)['bucket_id'] ?? $this->bucketId;

        #if(
            $this->getInitializedEntriesAndSendStatement();
    #) {


        #}

    }

    private function getInitializedEntriesAndSendStatement(?int $excludeQueueId = null) : bool
    {
        $this->lrsType = $this->lrsType ?? \ilEvents2LrsPlugin::getLrsType();

        $this->lrsRequest = $this->lrsRequest ?? new XapiRequest(
                $this->lrsType->getLrsEndpointStatementsLink(),
                $this->lrsType->getLrsKey(),
                $this->lrsType->getLrsSecret()
            );

        $statements = $this->getQueueEntriesWithStateInitialized(true, true);

        foreach ($statements as $queueId => $statement) {

            #usleep(10);

            if($this->lrsRequest->sendStatement($statement)) {

                $this->deleteQueueEntryById($queueId);
                #$this->updateQueueWithStateDeletableById($queueId);

            } else {

                usleep(10);

                $newState = (int)self::$STATE_CRON_EXEC_1;
                $newFailedDate = date('Y-m-d H:i:s');

                #$this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK] state++ updateQueueEntryWithStateAndFailedDateById(' . $entry['queue_id'] . ') ');
                $this->updateQueueEntryWithStateAndFailedDateById((int)$queueId, $newState, $newFailedDate);

            }

        }

        return true;
    }

}