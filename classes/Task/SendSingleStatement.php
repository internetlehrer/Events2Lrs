<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\Events2Lrs\Task;


use ILIAS\{DI\Container,
    BackgroundTasks\Exceptions\InvalidArgumentException,
    BackgroundTasks\Implementation\Tasks\AbstractJob,
    BackgroundTasks\Types\SingleType,
    BackgroundTasks\Implementation\Values\ScalarValues\IntegerValue,
    BackgroundTasks\Types\Type,
    BackgroundTasks\Value,
    BackgroundTasks\Observer,
    BackgroundTasks\Implementation\Bucket\State,
    Plugin\Events2Lrs\Xapi\Request\XapiRequest};

/**
 * Class SendSingleStatement
 *
 * @package ILIAS\Plugin\Events2Lrs\Task
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */
class SendSingleStatement extends AbstractJob
{
    const DEBUG = false;

    /**
     * @var Container
     */
    protected $dic;

    use \ILIAS\Plugin\Events2Lrs\Model\DbEvents2LrsQueue;

    public function __construct()
    {
        global $DIC; /**@var Container $DIC */

        $this->dic = $DIC;
    }

    /**
     * @return Type[] Classof the Values
     */
    public function getInputTypes() : array
    {
        return [
            new SingleType(IntegerValue::class), // json_encoded parameters
        ];

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

    /**
     * @param Value[] $input
     * @param Observer $observer Notify the bucket about your progress!
     * @return Value
     * @throws InvalidArgumentException
     */
    public function run(array $input, Observer $observer) : Value
    {
        $output = new IntegerValue();

        $output->setValue(0);

        $this->setQueueIdAndLoadEntry($input[0]->getValue());

        $lrsType = \ilEvents2LrsPlugin::getLrsType();

        $lrsRequest = new XapiRequest(
            $lrsType->getLrsEndpointStatementsLink(),
            $lrsType->getLrsKey(),
            $lrsType->getLrsSecret()
        );

        $statusLrsRequest = $lrsRequest->sendStatement($this->statement);

        $output->setValue((int)$statusLrsRequest);

        if($statusLrsRequest) {

            $this->deleteQueueEntryById($this->queueId);

        } else {

            $this->updateQueueEntryWithStateAndFailedDateById($this->queueId, self::$STATE_CRON_EXEC_1, date('Y-m-d H:i:s'));

            $this->dic->logger()->root()->log('[BT JOB] ################## [BT JOB] FAILED sendStatement ');

        }

        $observer->notifyState(State::FINISHED);

        new HandleQueueEntries(0);

        $this->dic->backgroundTasks()->persistence()->deleteBucketById(
            $this->bucketId ?? $this->getBucketIdFromQueueEntryById($this->queueId)
        );

        return $output;
    }

}