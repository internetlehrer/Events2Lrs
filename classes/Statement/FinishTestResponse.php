<?php

namespace ILIAS\Plugin\Events2Lrs\Statement;


use assQuestionGUI;
use ilCmiXapiDateTime;
use ilCmiXapiLrsType;
use ilDatabaseException;
use ilObjectNotFoundException;
use ilObjTest;


class FinishTestResponse extends AbstractStatement
{

    public static $INTERACTION_TYPES = [
        'assSingleChoice' => 'choice',
        'assMultipleChoice' => 'choice',
        'assTextQuestion' => 'long-fill-in',
        'assNumeric' => 'numeric',
    ];


    /**
     * @var array
     */
    public $user_solutions;

    /**
     * @var array
     */
    public $ass_details;

    /**
     * @var array
     */
    public $test_details;

    /**
     * @var ilObjTest
     */
    public $testObj;

    /**
     * @var assQuestionGUI
     */
    public $questionUi;


    public function __construct(ilCmiXapiLrsType $lrsType, array $eventParam = [])
    {

        $eventParam['event'] = 'finishTestResponse';

        parent::__construct($lrsType, $eventParam);

        $this->ass_details = $eventParam['values'];

        $this->test_details = $eventParam['test_details'];

        $this->testObj = $eventParam['ilTestObj'];

        $this->questionUi = $eventParam['questionUi'];

        $this->user_solutions = $eventParam['solutionsRaw'];

    }



    public function buildTimestamp() : string
    {
        /* Fetch Test Result Timestamp as fallback for unanswered questions */
        $raw_timestamp = $this->test_details['result_tstamp'];
        if (count($this->user_solutions) > 0) {
            /* If we have a user_solution, fetch solution timestamp instead */
            $raw_timestamp = $this->user_solutions[0]['tstamp'];
        }
        $timestamp = new ilCmiXapiDateTime($raw_timestamp, IL_CAL_UNIX);
        return $timestamp->toXapiTimestamp();
    }

    public function hasResult() : bool
    {
        return $this->ass_details !== null;
    }

    /**
     * @return array
     */
    public function buildResult(): array
    {
        $result = [
            'score' => [
                'scaled' => $this->ass_details['reached'] / $this->ass_details['max'],
                'raw' => $this->ass_details['reached'],
                'min' => 0,
                'max' => $this->ass_details['max'],
            ],
            'completion' => $this->ass_details['answered'] == 1,
        ];

        if (count($this->user_solutions) > 0 && $this->getInteractionType() !== null) {

            $result['response'] = $this->buildUserResponse();

        }

        return $result;
    }

    public function buildUserResponse()
    {
        $solutions = [];
        foreach ($this->user_solutions as $key => $solution) {
            $solutions[] = $solution['value1'];
        }

        return implode('[,]', $solutions);
    }

    /**
     * @return array
     */
    public function _buildVerb() : array
    {
        return [
            'id' => "http://adlnet.gov/expapi/verbs/answered",
            'display' => [$this->getLocale() => "answered"]
        ];
    }

    public function getInteractionType()
    {
        if (array_key_exists($this->ass_details['type'], self::$INTERACTION_TYPES)) {
            return self::$INTERACTION_TYPES[$this->ass_details['type']];
        }
        return null;
    }

    /* Placeholder */
    public function buildObject(): array
    {
        $objectProperties = [
            #'id' => $this->buildContext()['contextActivities']['parent']['id'] . '/' . $this->ass_details['qid'],
            'id' => $this->buildContext()['contextActivities']['parent']['id'],
            'definition' => [
                'name' => [$this->getLocale() => $this->ass_details['title']],
                'description' => [$this->getLocale() => $this->questionUi->object->getQuestion()],
                'type' => 'http://adlnet.gov/expapi/activities/cmi.interaction'
            ]
        ];

        if ($this->getInteractionType() !== null) {
            $objectProperties['definition']['interactionType'] = $this->getInteractionType();
            if ($this->getInteractionType() === 'choice') {
                list($objectProperties['definition']['choices'], $objectProperties['definition']['correctResponsesPattern']) = $this->buildChoicesList();
            } else if ($this->getInteractionType() === 'numeric') {
                $objectProperties['definition']['correctResponsesPattern'] = $this->buildNumericCorrectResponsesPattern();
            } else if ($this->getInteractionType() === 'long-fill-in') {
                $objectProperties['definition']['correctResponsesPattern'] = $this->buildFillInCorrectResponsesPattern();
            }
        }

        return $objectProperties;
    }

    public function buildChoicesList(): array
    {
        $choices = [];
        $correctResponsesPattern = [];
        if (isset($this->questionUi->object->answers)) {
            foreach ($this->questionUi->object->answers as $id => $answer) {
                $choices[$id] = [
                    'id' => (string)$id,
                    'description' => [$this->getLocale() => $answer->getAnswertext()]
                ];
                if ($this->ass_details['type'] == 'assMultipleChoice' && $answer->getPointsChecked() > 0) {
                    $correctResponsesPattern[] = (string)$id;
                } else if ($this->ass_details['type'] == 'assSingleChoice' && $answer->getPoints() > 0) {
                    $correctResponsesPattern[] = (string)$id;
                }
            }
        }

        return [$choices, $correctResponsesPattern];
    }

    public function buildNumericCorrectResponsesPattern(): array
    {
        return [$this->questionUi->object->getLowerLimit() . '[:]' . $this->questionUi->object->getUpperLimit()];
    }

    public function buildFillInCorrectResponsesPattern(): array
    {
        $correctResponsesPattern = [];
        if (isset($this->questionUi->object->answers)) {
            foreach ($this->questionUi->object->answers as $id => $answer) {
                if ($answer->getPointsChecked() > 0) {
                    $correctResponsesPattern[] = $answer->getAnswertext();
                }
            }
        }

        return $correctResponsesPattern;
    }


    /**
     * @return array
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    public function buildContext(): array
    {
        $context = parent::buildContext();

        $context['contextActivities']['parent'] = $this->getObjectProperties($this->testObj);

        return $context;
    }
}
