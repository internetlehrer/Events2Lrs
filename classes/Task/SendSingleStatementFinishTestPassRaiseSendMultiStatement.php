<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\Events2Lrs\Task;


use ilCmiXapiLrsType;
use ilDatabaseException;
use ilDateTimeException;
use ilLoggerFactory;
use ILIAS\{DI\Container,
    BackgroundTasks\Exceptions\InvalidArgumentException,
    BackgroundTasks\Implementation\Tasks\AbstractJob,
    BackgroundTasks\Types\SingleType,
    BackgroundTasks\Implementation\Values\ScalarValues\IntegerValue,
    BackgroundTasks\Types\Type,
    BackgroundTasks\Value,
    BackgroundTasks\Observer,
    BackgroundTasks\Implementation\Bucket\State,
    DI\Exceptions\Exception,
    Plugin\Events2Lrs\Statement\FinishTestPass,
    Plugin\Events2Lrs\Statement\FinishTestResponse,
    Plugin\Events2Lrs\Xapi\Request\XapiRequest,
    Plugin\Events2Lrs\Xapi\Statement\XapiStatementBuilder,
    Plugin\Events2Lrs\Xapi\Statement\XapiStatementList};
use ilObjectNotFoundException;
use ilPluginException;

/**
 * Class SendSingleStatementFinishTestPassRaiseFinishTestResponse
 *
 * @package ILIAS\Plugin\Events2Lrs\Task
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */
class SendSingleStatementFinishTestPassRaiseSendMultiStatement extends SendSingleStatement
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
     * @var FinishTestPass
     */
    protected $statementFinishTestPass;
    /**
     * @var \ilLogger
     */
    protected $logger;
    /**
     * @var array
     */
    protected $finishTestPassParam;

    /**
     * @var array
     */
    protected $finishTestResponseEntries;

    use \ILIAS\Plugin\Events2Lrs\Model\DbEvents2LrsQueue;

    public function __construct()
    {
        global $DIC; /**@var Container $DIC */

        $this->dic = $DIC;

        parent::__construct();

        $this->logger = $this->dic->logger()->root();
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
     * @throws \Exception
     */
    public function run(array $input, Observer $observer) : Value
    {
        $output = new IntegerValue();

        $output->setValue(0);

        $this->setQueueIdAndLoadEntry($input[0]->getValue());

        $this->lrsType = \ilEvents2LrsPlugin::getLrsType();

        $lrsRequest = new XapiRequest(
            $this->lrsType->getLrsEndpointStatementsLink(),
            $this->lrsType->getLrsKey(),
            $this->lrsType->getLrsSecret()
        );

        $statusLrsRequest = $lrsRequest->sendStatement($this->statement);

        $output->setValue((int)$statusLrsRequest);

        if($statusLrsRequest) {

            $this->deleteQueueEntryById($this->queueId);

            $this->dic->logger()->root()->log('[BT JOB] ################## [BT JOB] SUCCESS sendStatement ');

        } else {

            $this->updateQueueEntryWithStateAndFailedDateById($this->queueId, self::$STATE_CRON_EXEC_1, date('Y-m-d H:i:s'));

            $this->dic->logger()->root()->log('[BT JOB] ################## [BT JOB] FAILED sendStatement ');

        }


        // send multiStatement with all finishTestResponseResults or fallback to store them as singleStatement for cronjob
        if((array_flip(\ilEvents2LrsPlugin::getSelectedEvents())['finishTestResponse'] ?? false)) {

            if($this->getFinishTestResponseEntries()) {

                $statementBuilder = new XapiStatementBuilder();

                foreach ($this->finishTestResponseEntries as $finishTestResponseEntry) {

                    $param = array_replace($finishTestResponseEntry, ['event' => 'finishTestResponse']);

                    $statementFinishTestResponse = new FinishTestResponse($this->lrsType, $finishTestResponseEntry);

                    $statementBuilder->addStatementToStatementsList($statementFinishTestResponse);

                }

                $multiStatement = $statementBuilder->getStatementsList()->getPostBody();

                // try sending but fallback if prev or current lrsRequest failed
                if(!($statusLrsRequest && $lrsRequest->sendStatement($multiStatement))) {

                    // build singleStatementList from above created statements and add to param initialDbEntry
                    foreach ($statementBuilder->getXapiStatements() as $statement) {

                        $statementList = new XapiStatementList();

                        $statementList->addStatement($statement);

                        $param = array_replace($this->finishTestPassParam, ['event' => 'finishTestResponse', 'statement' => $statementList->getPostBody()]);

                        if($queueId = $this->addInitialDbEntrty($param)) {

                            $this->updateQueueEntryWithStateAndFailedDateById($queueId, self::$STATE_CRON_EXEC_1, date('Y-m-d H:i:s'));

                        }

                    }

                }

            }

        }


        // CleanUp BT
        $observer->notifyState(State::FINISHED);

        new HandleQueueEntries(0);

        $this->dic->backgroundTasks()->persistence()->deleteBucketById(

            $this->bucketId ?? $this->getBucketIdFromQueueEntryById($this->queueId)

        );

        return $output;

    }

    /**
     * @throws ilObjectNotFoundException
     * @throws ilPluginException
     * @throws ilDatabaseException
     * @throws ilDateTimeException
     */
    private function getFinishTestResponseEntries() : bool
    {

        $this->finishTestResponseEntries = [];

        $this->finishTestPassParam = json_decode($this->parameter, 1);

        try {

            $this->statementFinishTestPass = new FinishTestPass($this->lrsType, $this->finishTestPassParam, 'finishTestPass');

        } catch (Exception $e) {

            $this->logger->info('########## Events2Lrs | EXCEPTION $this->statementFinishTestPass = new FinishTestPass($this->lrsType, $this->finishTestPassParam, \'finishTestPass\');');

            $this->logger->dump(['SOURCE' => implode(' > ', [__CLASS__, __METHOD__, __LINE__]), 'ERROR' => $e]);

        }

        
        foreach ($this->statementFinishTestPass->results as $key => $values) {

            if ($values['qid']) {

                try {

                    $questionUi = $this->statementFinishTestPass->ilTestServiceGui->object->createQuestionGUI("", $values['qid']);

                    try {

                        $solutionsRaw = $questionUi->object->getSolutionValues($this->statementFinishTestPass->active_id, $this->statementFinishTestPass->pass);

                        $this->finishTestResponseEntries[] = array_replace($this->finishTestPassParam, [
                            'values' => $values,
                            'test_details' => $this->statementFinishTestPass->test_details,
                            'ilTestObj' => $this->statementFinishTestPass->ilTestObj,
                            'questionUi' => $questionUi,
                            'solutionsRaw' => $solutionsRaw,
                            'event' => 'finishTestResponse'
                        ]);

                    } catch (Exception $e) {

                        $this->logger->info('########## Events2Lrs | EXCEPTION $solutionsRaw = $questionUi->object->getSolutionValues($this->statementFinishTestPass->active_id, $this->statementFinishTestPass->pass);');

                        $this->logger->dump(['SOURCE' => implode(' > ', [__CLASS__, __METHOD__, __LINE__]), 'ERROR' => $e]);

                    }

                } catch (Exception $e) {

                    $this->logger->info('########## Events2Lrs | EXCEPTION $questionUi = $this->statementFinishTestPass->ilTestServiceGui->object->createQuestionGUI("", $values[\'qid\']);');

                    $this->logger->dump(['SOURCE' => implode(' > ', [__CLASS__, __METHOD__, __LINE__]), 'ERROR' => $e]);

                }

            }
        }

        return count($this->finishTestResponseEntries);

    }


}