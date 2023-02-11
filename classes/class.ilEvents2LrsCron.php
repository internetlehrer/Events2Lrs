<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

/**
 * Class ilEvents2LrsCron
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 */


require_once dirname(__DIR__) . "/vendor/autoload.php";

use \ILIAS\DI\Container;

class ilEvents2LrsCron extends ilCronJob
{
	const JOB_ID = 'sendstatements';

    /**
     * @var Container
     */
    protected $dic;

    /**
     * @var ilEvents2LrsPlugin
     */
    protected $plugin;

	public function __construct()
	{
        global $DIC; /**@var Container $DIC */

        $this->dic = $DIC;

        $this->plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Cron', 'crnhk', 'Events2Lrs');

        $this->dic->logger()->root()->log(' init CronJob');
	}
	
	public function getId() : string
    {
		return self::JOB_ID;
	}

    public function getTitle() : string
    {
        return $this->plugin::PLUGIN_NAME;
    }

    public function getDescription() : string
    {
        return $this->plugin->txt("cronjob_description");
    }

    public function getDefaultScheduleType() : int
    {
        return self::SCHEDULE_TYPE_IN_HOURS;
    }

    public function getDefaultScheduleValue()
    {
        return 1;
    }

    public function hasAutoActivation() : bool
    {
        return true;
    }

    public function hasFlexibleSchedule() : bool
    {
        return false;
    }

	public function run()
    {
        $this->dic->logger()->root()->log(' try run x');

		$cronResult = new ilCronJobResult();
        $cronResult->setStatus(ilCronJobResult::STATUS_NO_ACTION);

        try {
            $this->dic->event()->raise('Services/Tracking', 'sendAllStatements', [
                'obj_id' => 1,
                'ref_id' => 1,
                'usr_id' => $this->dic->user()->getId()
            ]);
            $cronResult->setStatus(ilCronJobResult::STATUS_OK);
            $this->dic->logger()->root()->log('RAISED sendAllStatements');
        } catch(Exception $e) {
            $cronResult->setStatus(ilCronJobResult::STATUS_FAIL);
            $this->dic->logger()->root()->dump($e);
        }

		return $cronResult;
	}

}
