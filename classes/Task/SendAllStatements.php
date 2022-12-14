<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\Events2Lrs\Task;

use ILIAS\DI\Container;
use ILIAS\Plugin\Events2Lrs\Xapi\Request\XapiRequest;
use ilLoggerFactory;
use ilCmiXapiLrsType;

/**
 * Class SendAllStatements
 *
 * @package ILIAS\Plugin\Events2Lrs\Task
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */
class SendAllStatements
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

        if($this->loadAllQueueEntriesForCronJob()) {
            $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK] n entries: ' . count($this->queueEntries));
            $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK] run() sending all failed statements ');
            $this->run();
            #$this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK] deleteQueueEntryById(cronJobQueueId) ');
            #$this->deleteQueueEntryById();
        }
        $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK] deleteQueueEntryById(cronJobQueueId) ');
        $this->deleteQueueEntryById();
    }


    public function run() : bool
    {
        $this->lrsType = $this->lrsType ?? \ilEvents2LrsPlugin::getLrsType();

        $this->lrsRequest = $this->lrsRequest ?? new XapiRequest(
            $this->lrsType->getLrsEndpointStatementsLink(),
            $this->lrsType->getLrsKey(),
            $this->lrsType->getLrsSecret()
        );

        foreach ($this->queueEntries as $queueId => $entry) {

            usleep(10);

            $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK]');
            $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK]');
            $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK]');
            $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK]');
            $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK]');
            $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK]');
            $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK]');
            $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK] sendStatement(' . $entry['queue_id'] . ') ');
            $this->dic->logger()->root()->dump($entry);


            if($this->lrsRequest->sendStatement($entry['statement'])) {

                #$this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK] deleteQueueEntryById(stateFaildQueueId) ');
                #$this->deleteQueueEntryById($queueId);

                $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK] updateQueueWithStateDeletableById(stateFaildQueueId) ');
                $this->updateQueueWithStateDeletableById($entry['queue_id']);

            } else {

                usleep(10);

                $newState = (int)$entry['state'] + 1;
                $newFailedDate = date('Y-m-d H:i:s');

                $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK] state++ updateQueueEntryWithStateAndFailedDateById(' . $entry['queue_id'] . ') ');
                $this->updateQueueEntryWithStateAndFailedDateById((int)$entry['queue_id'], $newState, $newFailedDate);

            }

        }

        $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK] deleteQueueEntryById(' . $this->queueId . ') ');
        $this->deleteQueueEntryById();

        return true;

    }

}