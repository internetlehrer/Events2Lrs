<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

require_once __DIR__ . "/../vendor/autoload.php";

use ILIAS\DI\Container;
use ILIAS\Plugin\Events2Lrs\Event\Services\Tracking\SendStatementsByQueueId;
use ILIAS\Plugin\Events2Lrs\Model\DbEvents2LrsQueue;

/**
 * Class ilEvents2LrsConfig
 *
 * @author      Björn Heyser <info@bjoernheyser.de>
 *
 * @package     Services/AssessmentQuestion
 */
class ilEvents2LrsConfigGUI extends ilPluginConfigGUI
{
    /**
     * @var array
     */
    private $onScreenMessage = [];

    /**
	 * @var ilEvents2LrsPlugin
	 */
	protected $plugin_object;

    /**
     * @var Container
     */
    protected $dic;

    /**
     * @var ilTabsGUI
     */
    protected $tabs;

    /**
     * @var ilGlobalPageTemplate
     */
    protected $tpl;

    /** @var ilLanguage $lng */
    protected $lng;

    /**
     * @var ilCtrl
     */
    protected $ctrl;

    /**
     * @var ilSetting $settings
     */
    protected $settings;

    /** @var bool $isPostRequestAllowed */
    private $isPostRequestAllowed;


    use DbEvents2LrsQueue;

    public function __construct()
    {
        global $DIC; /** @var Container $DIC */

        $this->dic = $DIC;

        $this->tabs = $this->dic->tabs();

        $this->tpl = $this->dic->ui()->mainTemplate();

        $this->lng = $this->dic->language();

        $this->ctrl = $this->dic->ctrl();

        $this->settings = $this->dic->settings();

        $this->isPostRequestAllowed = $this->hasAvailableLrsTypes();

    }

    public function performCommand($cmd)
	{
        if(isset($this->dic->http()->request()->getQueryParams()['table_failed_statements_table_nav'])) {
            $cmd = 'tab_failed_statements';
        }

        switch ($cmd) {
            case 'configure':
            case 'tab_lrs_type':
                $this->initTabs('tab_lrs_type');
                $this->tabLrsType();
                break;

            case 'save_lrs_type':
                $this->saveLrsType();
                break;


            case 'tab_events':
                $this->initTabs('tab_events');
                $this->tabEvents();
                break;

            case 'save_events':
                $this->saveEvents();
                break;


            case 'tab_failed_statements':
            case 'apply_filter_failed_statements':
            case 'reset_filter_failed_statements':
                $this->countQueueEntries();
                $this->initTabs('tab_failed_statements');
                $this->tabFailedStatements($cmd);
                break;

            case 'confirm_delete_statements':
                $this->confirmDeleteStatements();
                break;

            case 'send_failed_statements':
                $this->sendFailedStatements();
                break;

            case 'tab_xapi_endpoint':
                $this->ctrl->redirectByClass(ilEvents2LrsConfigGUI::class);
                break;

            case 'tab_tracking_verbs':
                $this->initTabs('tab_tracking_verbs');
                $this->tabTrackingVerbs();
                break;

            case 'save_tracking_verbs':
                $this->initTabs('tab_tracking_verbs');
                $this->saveTrackingVerbs();
                break;


            default:
                ilUtil::sendFailure($this->plugin_object->txt('not_supported_cmd') . $this->ctrl->getCmd(), true);
                #$this->tabFailedStatements();
                $this->ctrl->redirectByClass("ilobjcomponentsettingsgui", "listPlugins");
        }

	}

    private function initTabs(?string $tab = null) : void
    {
        if($tab) {

            $this->tabs->addTab('tab_lrs_type', 'LRS',
                $this->ctrl->getLinkTarget($this, 'tab_lrs_type')
            );

            $this->tabs->addTab('tab_events', 'Events',
                $this->ctrl->getLinkTarget($this, 'tab_events')
            );

            $this->tabs->addTab('tab_tracking_verbs', 'Verbs',
                $this->ctrl->getLinkTarget($this, 'tab_tracking_verbs')
            );

            $this->tabs->addTab('tab_failed_statements', 'Statements',
                $this->ctrl->getLinkTarget($this, 'tab_failed_statements')
            );

            /*
            $this->tabs->addNonTabbedLink('tab_xapi_endpoint', ilXapiEndpointPlugin::PLUGIN_NAME,
                $this->ctrl->getLinkTarget(new ilXapiEndpointConfigGUI(), 'configure')
            );
            */
            $this->tabs->activateTab($tab);

        }
    }

    protected function tabLrsType(ilPropertyFormGUI $form = null) : void
    {
        $this->setContent(($form ?? $this->buildFormLrsType())->getHTML());
    }

    protected function buildFormLrsType(): ilPropertyFormGUI
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $form = new ilPropertyFormGUI();

        if(!$this->isPostRequestAllowed) {
            return $form;
        }

        $form->setFormAction($DIC->ctrl()->getFormAction($this));
        $form->addCommandButton('save_lrs_type', $this->lng->txt('save'));

        $form->setTitle('Configuration');

        $item = new ilRadioGroupInputGUI('LRS-Type', 'lrs_type_id');
        $item->setRequired(true);

        #$types = ilPluginAdmin::isPluginActive("xxcf") ? ilEvents2LrsChangesQueue::getTypesData() : ilCmiXapiLrsTypeList::getTypesData(false);
        $types = ilCmiXapiLrsTypeList::getTypesData(false);

        foreach ($types as $type)
        {
            $option = new ilRadioOption($type['title'], $type['type_id'], $type['description']);
            $item->addOption($option);
        }

        $item->setValue($this->readLrsTypeId());

        $form->addItem($item);

        return $form;
    }

    /*
    protected function configure(ilPropertyFormGUI $form = null)
    {
        $this->tabLrsType($form);
    }
    */

    protected function saveLrsType()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */

        $form = $this->buildFormLrsType();

        if( !$form->checkInput() )
        {
            $this->tabLrsType($form);
        } else {


            $this->writeLrsTypeId($form->getInput('lrs_type_id'));

        }

        $DIC->ctrl()->redirect($this, 'tab_lrs_type');
    }

    protected function readLrsTypeId()
    {
        return $this->settings->get($this->plugin_object->getId() . '__lrs_type_id', 0);
    }

    protected function writeLrsTypeId($lrsTypeId)
    {
        $this->settings->set($this->plugin_object->getId() . '__lrs_type_id', $lrsTypeId);
    }


    protected function tabEvents(ilPropertyFormGUI $form = null) : void
    {
        $this->setContent(($form ?? $this->buildFormEvents())->getHTML());
    }

    protected function buildFormEvents(): ilPropertyFormGUI
    {
        $this->plugin_object = new ilEvents2LrsPlugin();

        $form = new ilPropertyFormGUI();

        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->addCommandButton('save_events', $this->lng->txt('save'));

        $form->setTitle('Events');

        $allEvents = array_keys(\ILIAS\Plugin\Events2Lrs\Task\TaskManager::getEventTask());

        $defaultEvents = $this->plugin_object::getDefaultEvents();

        foreach ($allEvents as $event) {

            if(in_array($event, $defaultEvents)) {

                continue;

            }

            $item = new ilCheckboxInputGUI($event, 'event[]');
            $item->setValue($event);
            /*
            if(in_array($event, $defaultEvents)) {
                $item->setChecked(true);
                $item->setDisabled(true);
            }*/
            if(in_array($event, $this->readEvents())) {
                $item->setChecked(true);
            }

            $item->setInfo($this->plugin_object->txt($event . '_info'));

//            if ($event == 'readCounterChange') {
//                $ni = new ilNumberInputGUI($this->plugin_object->txt('tracking_time_span_max'),'tracking_time_span_max');
//                $ni->setValue((string) $this->dic->settings()->get('tracking_time_span_max', 3600));
//                $ni->setMaxLength(6);
//                $ni->setSize(6);
//                $ni->setInfo($this->plugin_object->txt('tracking_time_span_max_info'));
//                $item->addSubItem($ni);
//            }
            $form->addItem($item);
        } // EOF foreach ($allEvents as $allEvent)

        foreach ($defaultEvents as $event) {

            $item = new ilHiddenInputGUI('event[]');

            $item->setValue($event);

            $form->addItem($item);

        } // EOF foreach ($defaultEvents as $allEvent)

        return $form;
    }

    protected function saveEvents()
    {
        $form = $this->buildFormEvents();

        $form->setValuesByPost();

        if( !$form->checkInput() )
        {
            $this->tabEvents($form);
        }

        $this->writeEvents($form->getInput('event') ?? []);
//        if($form->getInput('tracking_time_span_max') !== null && $form->getInput('tracking_time_span_max') !== '') {
//            $this->settings->set('tracking_time_span_max', $form->getInput('tracking_time_span_max'));
//        }

        $this->ctrl->redirect($this, 'tab_events');
    }

    protected function readEvents()
    {
        return json_decode($this->settings->get($this->plugin_object->getId() . '__events', 0));
    }

    protected function writeEvents($events)
    {
        $defaultEvents = $this->plugin_object::getDefaultEvents();
        $newEvents = array_diff($events, $defaultEvents, [""]);
        $events = array_merge($defaultEvents, $newEvents);

        $this->settings->set($this->plugin_object->getId() . '__events', json_encode($events));
    }


    protected function tabTrackingVerbs(ilPropertyFormGUI $form = null) : void
    {

        $this->setContent(($form ?? $this->buildFormTrackingVerbs())->getHTML());

    }

    protected function buildFormTrackingVerbs(): ilPropertyFormGUI
    {

        $form = new ilPropertyFormGUI();

        if(!$this->isPostRequestAllowed) {
            return $form;
        }

        $form->setFormAction($this->ctrl->getFormAction($this));

        $form->addCommandButton('save_tracking_verbs', $this->lng->txt('save'));

        $form->setTitle('Tracking Verbs');

        $verbList = ilEvents2LrsPlugin::getAllVerbs();

        $untrackedVerbs= ilEvents2LrsPlugin::getUntrackedVerbs();

        foreach ($verbList as $verb) {

            $cb = new ilCheckboxInputGUI($verb, 'verb[' . $verb . ']'); #

            $cb->setValue(1);

            $cb->setChecked(!in_array($verb, $untrackedVerbs));

            $form->addItem($cb);

        }

        return $form;
    }


    protected function saveTrackingVerbs()
    {

        $form = $this->buildFormTrackingVerbs();

        if( !$form->checkInput() )
        {

            $this->tabTrackingVerbs($form);

        }

        $this->saveUntrackedVerbs(array_keys($form->getInput('verb') ?? []));

        $this->ctrl->redirect($this, 'tab_tracking_verbs');
    }

    protected function saveUntrackedVerbs($receivedVerbs) : void
    {

        $verbList = ilEvents2LrsPlugin::getAllVerbs();

        $untrackedVerbs = array_values(array_diff($verbList, $receivedVerbs));

        $untrackedVerbs = json_encode($untrackedVerbs);

        $this->settings->set($this->plugin_object->getId() . '__untracked_verbs', $untrackedVerbs);

    }


    protected function tabFailedStatements(string $cmd) : void
    {
        $tblContent = '';

        $filterValue = [];

        $this->setFailedStatementSummaries();

        if($this->countQueueEntries(self::$STATE_CRON_FAILED)) {
            $tableGui = new ilEvents2LrsTableGUI($this);

            if($cmd === 'apply_filter_failed_statements') {
                /** @var ilDateTimeInputGUI $filterDateFailed */
                $filterDateFailed = $tableGui->getFilterItemByPostVar('date_failed');
                $filterDateFailed->setValueByArray($this->dic->http()->request()->getParsedBody());
                $filterValue['date_failed'] = $filterDateFailed->getDate(); # $this->dic->http()->request()->getParsedBody()['date_failed'];
                $filterValue['date_failed'] = is_null($filterValue['date_failed']) ? '' : substr($filterValue['date_failed']->__toString(), 0, 10);

                /** @var ilTextInputGUI $filterObjId */
                $filterObjId = $tableGui->getFilterItemByPostVar('obj_id');
                $filterObjId->setValueByArray($this->dic->http()->request()->getParsedBody());
                $filterValue['obj_id'] = $filterObjId->getValue();
            }

            $tableGui->setData(
                $tableGui->withRowSelector(
                    $this->getQueueEntriesWithStateFailed($filterValue)
                )
            );

            $modalStyle = '<style>.modal-dialog {width: 80%;}</style>';

            $tblContent = $modalStyle . $tableGui->getHTML();
        }

        $this->setContent($tblContent);
    }



    private function setFailedStatementSummaries() : bool
    {
        if(!$this->numQueueEntries) {
            $this->onScreenMessage[] = $this->renderMessage('success',$this->numQueueEntries . ' Statements');
            return true;
        }

        $setNumCronExec = function($state, $type = 'confirmation') {
            $txtWithState = ' Statements mit Status ';
            if($numCronExec = $this->countQueueEntries($state)) {
                $this->onScreenMessage[] = $this->renderMessage($type,$numCronExec . $txtWithState . $state);
            }
        };

        $setNumCronExec(self::$STATE_CRON_EXEC_1);
        $setNumCronExec(self::$STATE_CRON_EXEC_2);
        $setNumCronExec(self::$STATE_CRON_EXEC_3);
        $setNumCronExec(self::$STATE_CRON_FAILED, 'failure');

        return true;
    }

    function renderMessage(string $type = 'info', string $msg = '') : string
    {
        /** @var ILIAS\UI\Implementation\Component\MessageBox\MessageBox $type */
        $msgBox = $this->dic->ui()->factory()->messageBox()->$type($msg);
        return $this->dic->ui()->renderer()->render($msgBox);
    }

    private function confirmDeleteStatements() : void
    {
        $this->deleteStatements();
        $this->ctrl->redirect($this, 'tab_failed_statements');
    }

    private function deleteStatements() : void
    {
        foreach($this->dic->http()->request()->getParsedBody()['queue_id'] ?? [] AS $queueId) {
            $this->deleteQueueEntryById($queueId);
        }
    }

    private function sendFailedStatements() : void
    {
        new SendStatementsByQueueId($this->dic->http()->request()->getParsedBody()['queue_id']);
        $this->ctrl->redirect($this, 'tab_failed_statements');
    }


    protected function setContent(string $content) : void
    {
        if( !$this->hasAvailableLrsTypes() ) {
            ilUtil::sendFailure($this->plugin_object->txt('lrs_type_not_set'));
        }

        $this->tpl->setContent(
            implode('', $this->onScreenMessage) .
            $content
        );


    }

    public function hasAvailableLrsTypes() : bool
    {
        return (bool)count(ilCmiXapiLrsTypeList::getTypesData(false));
    }


    public function isPostRequestAllowed() : bool
    {
        return $this->isPostRequestAllowed;
    }

}
