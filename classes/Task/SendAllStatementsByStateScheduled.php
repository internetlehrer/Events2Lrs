<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\Events2Lrs\Task;

use ilCmiXapiLrsType;
use ilLoggerFactory;
use ILIAS\{BackgroundTasks\Implementation\Bucket\State,
    BackgroundTasks\Implementation\Tasks\AbstractJob,
    BackgroundTasks\Implementation\Values\ScalarValues\IntegerValue,
    BackgroundTasks\Observer,
    BackgroundTasks\Types\SingleType,
    BackgroundTasks\Types\Type,
    BackgroundTasks\Value,
    DI\Container,
    Plugin\Events2Lrs\Xapi\Request\XapiRequest};

/**
 * Class SendAllStatementsByStateScheduled
 *
 * @package ILIAS\Plugin\Events2Lrs\Task
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */
class SendAllStatementsByStateScheduled extends AbstractJob
{

    const DEBUG = false;
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

    public function __construct()
    {
        global $DIC; /**@var Container $DIC */

        $this->dic = $DIC;

    }


    public function run(array $input, Observer $observer) : Value
    {
        $this->lrsType = $this->lrsType ?? \ilEvents2LrsPlugin::getLrsType();

        $this->lrsRequest = $this->lrsRequest ?? new XapiRequest(
            $this->lrsType->getLrsEndpointStatementsLink(),
            $this->lrsType->getLrsKey(),
            $this->lrsType->getLrsSecret()
        );

        $statements = $this->getQueueEntriesWithStateScheduled(true, true);

        foreach ($statements as $queueId => $statement) {

            #usleep(10);

            if($this->lrsRequest->sendStatement($statement)) {

                $this->updateQueueWithStateDeletableById($queueId);

            } else {

                usleep(10);

                $newState = (int)self::$STATE_CRON_EXEC_1;
                $newFailedDate = date('Y-m-d H:i:s');

                #$this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK] state++ updateQueueEntryWithStateAndFailedDateById(' . $entry['queue_id'] . ') ');
                $this->updateQueueEntryWithStateAndFailedDateById((int)$queueId, $newState, $newFailedDate);

            }

        }

        if(count($statements)) {

            $this->dic->event()->raise('Services/Tracking', 'handleQueueEntries', [
                'obj_id' => 1,
                'ref_id' => 1,
                'usr_id' => $this->dic->user()->getId(),
                #'bucket_id' => $observer->buc
            ]);
        }

        $this->dic->logger()->root()->log('[CRON JOB TASK] ################## [CRON JOB TASK] deleteQueueEntryById(' . $this->queueId . ') ');
        #$this->deleteQueueEntryById();
        $observer->notifyState(State::FINISHED);

        $output = new IntegerValue();
        $output->setValue(1);

        return $output;


    }

    /**
     * @return Type[] Classof the Values
     */
    public function getInputTypes() : array
    {
        return [
            new SingleType(IntegerValue::class), // json_encoded parameters
        ];
        /*
        return [
            new SingleType(IntegerValue::class), // refId
            new SingleType(IntegerValue::class), // objId
            new SingleType(IntegerValue::class), // usrId
            new SingleType(StringValue::class), // event
            new SingleType(StringValue::class), // date
            new SingleType(StringValue::class), // parameter
        ];
        */
    }

    /**
     * @return Type
     */
    public function getOutputType() : Type
    {
        return new SingleType(IntegerValue::class);
    }

    /**
     * @return bool returns true iff the job's output ONLY depends on the input. Stateless task
     *              results may be cached!
     */
    public function isStateless() : bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getExpectedTimeOfTaskInSeconds() : int
    {
        return 1;
    }
}