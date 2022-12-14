<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

use ILIAS\DI\Container;

class ilEvents2LrsTableGUI extends ilTable2GUI {

    /** @var Container $dic */
    protected $dic;

    /** @var ilLanguage $lng */
    protected $lng;


    /** @var ilEvents2LrsConfigGUI $parent_obj */
    protected $parent_obj;

    /** @var ilEvents2LrsPlugin|ilPlugin|null $plugin_object */
    private $plugin_object;

    /**
     * @var array $filter
     */
    protected $filter = [];


    function __construct($a_parent_obj, $a_parent_cmd = '', $a_template_context = '') 
    {
        global $DIC; /** @var Container $DIC */

        $this->dic = $DIC;

        $this->lng = $this->dic->language();

        $this->parent_obj = $a_parent_obj;
    	// this uses the cached plugin object
		$this->plugin_object = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Cron', 'crnhk', 'Events2Lrs');

        $this->setId('table_failed_statements');

        parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);

        $this->initColumns($this->plugin_object);

        $this->setEnableHeader(true);

        $this->setExternalSorting(false);

        $this->setExternalSegmentation(false);

        $this->setShowRowsSelector(false);

        $this->setDefaultOrderField('date_failed');

        $this->setDefaultOrderDirection('asc');

        $this->enable('sort');

        $this->setRowTemplate('tpl.failed_statements_table_row.html', 'Customizing/global/plugins/Services/Cron/CronHook/Events2Lrs');

        $this->setEnableNumInfo(true);

        $this->setFormAction($this->dic->ctrl()->getFormAction($this->parent_obj, 'tab_failed_statements'));

        if( $this->parent_obj->hasAvailableLrsTypes() ) {

            $this->addMultiCommand('send_failed_statements', $this->plugin_object->txt('send_failed_statements'));
        }

        $this->addMultiCommand('confirm_delete_statements', $this->lng->txt('delete'));

        $this->setTopCommands(false);

        $this->setSelectAllCheckbox('queue_id');

        $this->initFilter();

        $this->setFilterCommand('apply_filter_failed_statements');

        $this->setResetCommand('reset_filter_failed_statements');



    }

    public function initColumns(ilEvents2LrsPlugin $lng)
    {
        $this->addColumn('', '', '5%');

        #$this->addColumn($lng->txt('state'), 'state', '5%');

        $this->addColumn($lng->txt('date_failed'), 'date_failed', '');

        $this->addColumn($lng->txt('event'), 'event', '');

        $this->addColumn($lng->txt('date'), 'date', '');

        $this->addColumn($lng->txt('ref_id'), 'ref_id', '5%');

        $this->addColumn($lng->txt('obj_id'), 'obj_id', '5%');

        $this->addColumn($lng->txt('usr_id'), 'usr_id', '5%');

        $this->addColumn($lng->txt('statement'), '', '');

    }


    /**
     * Fill a single data row.
     * @param array $a_set
     * @throws ilDateTimeException
     */
    protected function fillRow($a_set) 
    {
        $a_set['date'] = new ilDateTime($a_set['date'], IL_CAL_DATETIME);
        $a_set['date'] = ilDatePresentation::formatDate($a_set['date']);

        $a_set['date_failed'] = new ilDateTime($a_set['date_failed'], IL_CAL_DATETIME);
        $a_set['date_failed'] = ilDatePresentation::formatDate($a_set['date_failed']);

        $this->tpl->setVariable('ROWSELECTOR', $a_set['rowSelector']);
        #$this->tpl->setVariable('STATE', $a_set['state']);
        $this->tpl->setVariable('DATE_FAILED', $a_set['date_failed']);
        $this->tpl->setVariable('EVENT', $a_set['event']);
        $this->tpl->setVariable('DATE', $a_set['date']);
        $this->tpl->setVariable('REF_ID', $a_set['ref_id']);
        $this->tpl->setVariable('OBJ_ID', $a_set['obj_id']);
        $this->tpl->setVariable('USR_ID', $a_set['usr_id']);
        $this->tpl->setVariable('QUEUE_ID', $a_set['queue_id']);
        $this->tpl->setVariable('STATEMENT', preg_replace(['%\s%'], [''], substr($a_set['statement'], 1, -1)));
        $this->tpl->setVariable('PARAMETERS', preg_replace(['%\s%'], [''], substr($a_set['parameter'], 0)));
        $this->tpl->setVariable('MODAL', $this->linkShowModalStatement(
            '<pre><code>' . json_encode(json_decode($a_set['statement'])[0], JSON_PRETTY_PRINT) . '</code></pre>'
        ));



    }


    public function withRowSelector( array $a_data ): array
    {
        foreach($a_data as $queueId => $data) {

            $checkbox = new ilCheckboxInputGUI('', 'queue_id[]');
            $checkbox->setValue($queueId);
            $checkbox->setChecked( isset($_POST) && isset($_POST['queue_id']) && array_search($queueId, $_POST['queue_id']) );
            #$returnData[$key]['rowSelector'] = $checkbox->render();
            $a_data[$queueId] = array_merge(
                ['rowSelector' => $checkbox->render()],
                $data
            );
        }
        #echo '<pre>'; var_dump([$returnData]); exit;
        return $a_data;
    }

    public function initFilter()
    {
        $lng = $this->lng;

        $this->filter["date_failed"] = $this->addFilterItemByMetaType(
            "date_failed",
            ilTable2GUI::FILTER_DATE,
            false,
            $this->plugin_object->txt("date_failed")
        );

        $this->filter["obj_id"] = $this->addFilterItemByMetaType(
            "obj_id",
            ilTable2GUI::FILTER_TEXT,
            false,
            $this->plugin_object->txt("obj_id")
        );

            #$this->addFilterItem($this->filter["period"]);
    }

    private function linkShowModalStatement(string $statement) : string
    {
        $factory = $this->dic->ui()->factory();
        $renderer = $this->dic->ui()->renderer();

        $modal = $factory->modal()->roundtrip('Statement', $factory->legacy($statement));
        $modal->withCancelButtonLabel('close');
        $button = $factory->button()->standard('Modal', '#')->withOnClick($modal->getShowSignal());
        #$button->withAdditionalOnLoadCode()
        return $renderer->render([$button, $modal]);
    }

}

?>