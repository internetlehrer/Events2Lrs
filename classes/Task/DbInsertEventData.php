<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\Events2Lrs\Task;

use ILIAS\DI\Container;
use ILIAS\BackgroundTasks\Implementation\Tasks\AbstractJob;
use ILIAS\BackgroundTasks\Types\SingleType;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\IntegerValue;
use ILIAS\BackgroundTasks\Types\Type;
use ILIAS\BackgroundTasks\Value;
use ILIAS\BackgroundTasks\Observer;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\StringValue;

/**
 * Class DbInsertEventData
 *
 * @package ILIAS\Plugin\Events2Lrs\Task
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */
class DbInsertEventData extends AbstractJob
{
    /**
     * @var Container
     */
    protected $dic;

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
            new SingleType(IntegerValue::class), // refId
            new SingleType(IntegerValue::class), // objId
            new SingleType(IntegerValue::class), // usrId
            new SingleType(StringValue::class), // event
            new SingleType(StringValue::class), // date
            new SingleType(StringValue::class), // parameter
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
     * @param Value[]  $input
     * @param Observer $observer Notify the bucket about your progress!
     *
     * @return Value
     */
    public function run(array $input, Observer $observer) : Value
    {
        $output = new IntegerValue();
        $output->setValue(0);


        /** @var IntegerValue $refId */
        /** @var IntegerValue $objId */
        /** @var IntegerValue $usrId */
        /** @var StringValue $event */
        /** @var StringValue $date */
        /** @var StringValue $parameter */
        [$refId, $objId, $usrId, $event, $date, $parameter] = $input;

        if($refId->getValue() && $objId->getValue()) {
            $queueId = (int)$this->dic->database()->nextId('ev2lrs_queue');
            $values = [
                'queue_id' => array('integer', $queueId),
                'ref_id' => array('integer', $refId->getValue()),
                'obj_id' => array('integer', $objId->getValue()),
                'usr_id' => array('integer', $usrId->getValue()),
                'event' => array('text', $event->getValue()),
                'date' => array('timestamp', $date->getValue()), #date("Y-m-d H:i:s")
                'parameter' => array('text', $parameter->getValue())
            ];

            if ($this->dic->database()->insert('ev2lrs_queue', $values)) {
                $output->setValue($queueId);
            }
        }

        return $output;
    }

}