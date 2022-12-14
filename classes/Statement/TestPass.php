<?php

namespace ILIAS\Plugin\Events2Lrs\Statement;

use assQuestionGUI;
use ilCmiXapiLrsType;
use ILIAS\DI\Exceptions\Exception;
use ILIAS\Plugin\Events2Lrs\Xapi\Statement\XapiStatement;
use ilObjTest;
use ilObjTestGUI;
use ilObjUser;

class TestPass extends AbstractStatement
{

    /**
     * @var array|null $pass_details
     */
    public $pass_details;

    /**
     * @var array|null $test_details
     */
    public $test_details;

    /**
     * @var ilObjTest
     */
    public $ilTestObj;

    /**
     * @var ilObjTestGUI
     */
    public $ilTestServiceGui;

    /**
     * @var int
     */
    public $active_id;

    /**
     * @var int
     */
    public $pass;

    /**
     * @var array
     */
    public $results;

    /**
     * @var assQuestionGUI|null
     */
    public $questionUi;

    /**
     * @var TestPass
     */
    public $testResult;


    /**
     * @var int
     */
    public $user_id;


    public function __construct(ilCmiXapiLrsType $lrsType, array $param)
    {

        parent::__construct($lrsType, $param);

        #$this->logger->dump($param);

        $this->active_id = (int)$param['active_id'];

        $this->pass = (int)$param['pass'];

        try {

            $this->ilTestObj = new ilObjTest($this->refId);

        } catch (Exception $e) {

            $this->logger->info('########## Events2Lrs | EXCEPTION $this->ilTestServiceGui = new ilObjTestGUI($this->refId)');

            $this->logger->dump(['SOURCE' => implode(' > ', [__CLASS__, __METHOD__, __LINE__]), 'ERROR' => $e]);

        }


        try {

            $this->ilTestServiceGui = new ilObjTestGUI($this->refId);

        } catch (Exception $e) {

            $this->logger->info('########## Events2Lrs | EXCEPTION $this->ilTestServiceGui = new ilObjTestGUI($this->refId)');

            $this->logger->dump(['SOURCE' => implode(' > ', [__CLASS__, __METHOD__, __LINE__]), 'ERROR' => $e]);

        }


        try {

            $this->results = $this->ilTestObj->getTestResult($this->active_id, $this->pass);

        } catch (Exception $e) {

            $this->logger->info('########## Events2Lrs | EXCEPTION $this->results = $this->ilTestObj->getTestResult($this->active_id, $this->pass)');

            $this->logger->dump(['SOURCE' => implode(' > ', [__CLASS__, __METHOD__, __LINE__]), 'ERROR' => $e]);

        }


        $this->test_details = $this->results['test'];

        $this->pass_details = $this->results['pass'];

    }


    public function buildResult(): ?array
    {
        return [
            'score' => [
                'scaled' => $this->pass_details['percent'],
                'raw' => $this->pass_details['total_reached_points'],
                'min' => 0,
                'max' => $this->pass_details['total_max_points'],
            ],
            'completion' => false,
        ];

    }

}