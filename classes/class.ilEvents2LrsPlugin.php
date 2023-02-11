<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

use ILIAS\DI\Container;
use ILIAS\Plugin\Events2Lrs\Event\Services\Tracking\H5P;
use ILIAS\Plugin\Events2Lrs\Event\Services\Tracking\UpdateStatus;
use ILIAS\Plugin\Events2Lrs\Event\Services\Tracking\SendAllStatements;
use ILIAS\Plugins\Events2Lrs\Router\Events2LrsRouterGUI;
use ILIAS\Plugins\Events2Lrs\Router\HandlePsr7XapiRequest;


require_once __DIR__ . "/../vendor/autoload.php";

/**
 * Class ilEvents2LrsPlugin
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 */
class ilEvents2LrsPlugin extends ilCronHookPlugin {

	const PLUGIN_ID = "xelrs";

    const PLUGIN_NAME = "Events2Lrs";

    const PLUGIN_CLASS_NAME = ilEvents2LrsPlugin::class;

    CONST PLUGIN_COMPONENT = 'Plugins/Events2Lrs';

    /**
     * @var int
     */
    private $methodCallable = 1;

    /**
     * @var Container
     */
    protected $dic;

    /**
     * @var ilSetting $settings
     */
    protected $settings;

    /**
     * @var array
     */
    protected $taskExec = [];

    /**
     * @var array
     */
    private $storedParams = [];
    /**
     * @var mixed
     */
    public $allowedEvents;

    /**
     * @var bool
     */
    public $isHandlePsr7XapiRequest = false;
    /**
     * @var Closure
     */
    private $HandlePsr7XapiRequest;


    use ILIAS\Plugin\Events2Lrs\Model\DbEvents2LrsQueue;


    public static function initGlobal(Container $DIC, $a_name, $a_class, $a_source_file = null)
    {
        if ($a_source_file) {
            include_once $a_source_file;
            $GLOBALS[$a_name] = new $a_class;
        } else {
            $GLOBALS[$a_name] = $a_class;
        }

        $DIC[$a_name] = function ($c) use ($a_name) {
            return $GLOBALS[$a_name];
        };


    }

    /**
     * @inheritdoc
     */
	public function __construct()
    {

        global $DIC; /** @var Container $DIC */

        #self::initGlobal($DIC,'xApi', 'ILIAS\Plugins\Events2Lrs\DI\CmiXapi\Extension', 'Customizing/global/plugins/Services/Cron/CronHook/Events2Lrs/src/DI/CmiXapi/Extension.php');

        $this->dic = $DIC;

        $this->settings = $this->dic->settings();

        $this->allowedEvents = json_decode($this->settings->get(self::PLUGIN_ID . '__events', 0), 1);

		parent::__construct();

		$this->HandlePsr7XapiRequest = function() { return null; };

        $cmd = $this->dic->http()->request()->getQueryParams()['cmd'] ?? '';

        if(!$this->isHandlePsr7XapiRequest && php_sapi_name() !== "cli" && 'invokeServer' !== $cmd) {

            $this->isHandlePsr7XapiRequest = true;

            // todo check functionality of fixUiTemplate
            HandlePsr7XapiRequest::fixUITemplateInCronContext();

            $this->HandlePsr7XapiRequest = function() { return new HandlePsr7XapiRequest($this); };

            #new HandlePsr7XapiRequest($this);
        }

    }


    /**
     * @throws \ILIAS\HTTP\Response\Sender\ResponseSendingException
     */
    public function __destruct()
    {

        call_user_func($this->HandlePsr7XapiRequest);

        $this->HandlePsr7XapiRequest = function() { return null; };

        /*
        if($this->methodCallable) {
            usleep(self::$TIME_BEFORE_DELETE * 1000 + 500);
            $this->dic->logger()->root()->log("[PLUGIN EVENT HANDLER] ########################### [PLUGIN EVENT HANDLER] deleteAllQueueEntriesByStateDeletable !DEACTIVATED! see destructor of plugin class");
            $this->deleteAllQueueEntriesByStateDeletable();
            $this->methodCallable--;
        }
        */
    }

    public function getCronJobInstances() : array
    {
        return [
            new ilEvents2LrsCron()
        ];
    }

    public function getCronJobInstance($a_job_id) : ?ilEvents2LrsCron
    {
        switch ($a_job_id)
        {
            case ilEvents2LrsCron::JOB_ID:

                return new ilEvents2LrsCron();

            default:

                return null;
        }
    }
	
	
	/**
	 * @inheritdoc
	 */
	public function getPluginName() : string
    {
		return self::PLUGIN_NAME;
	}

    public static function hasLrsType() : bool
    {
        global $DIC; /** @var Container $DIC */

        $lrsTypeId = $DIC->settings()->get(self::PLUGIN_ID . '__lrs_type_id', 0);

        $DIC->logger()->root()->debug("LrsTypeCheck: ".$lrsTypeId);

        return (bool)$lrsTypeId;
    }

    public static function getLrsType() : ?ilCmiXapiLrsType
    {
        global $DIC; /** @var Container $DIC */

        $lrsTypeId = $DIC->settings()->get(self::PLUGIN_ID . '__lrs_type_id', false);

        $DIC->logger()->root()->debug("LrsType=".$lrsTypeId);

        if( $lrsTypeId )
        {
            return new ilCmiXapiLrsType($lrsTypeId);
        }
        else
        {
            return null;
        }
    }

	/**
	 * @inheritdoc
	 */
	protected function deleteData()
	{
		// Nothing to delete
	}

    public function afterInstall()
    {
        $this->settings->set($this->getId() . '__events', json_encode([]));

        $this->settings->set($this->getId() . '__untracked_verbs', json_encode([]));

        parent::afterInstall();
    }

    public function beforeActivation(): bool
    {
        ilEvents2LrsAsyncCron::installAsyncJob($this);

        return parent::beforeActivation(); // TODO: Change the autogenerated stub
    }

    public function afterActivation()
    {
        parent::afterActivation(); // TODO: Change the autogenerated stub

        if(!$this->settings->get($this->getId() . '__events')) {
            $this->settings->set($this->getId() . '__events', json_encode(self::getDefaultEvents()));
        }


    }

    public function afterDeactivation()
    {
        ilEvents2LrsAsyncCron::uninstallAsyncJob($this);

        parent::afterDeactivation(); // TODO: Change the autogenerated stub
    }

    protected function afterUninstall()
    {
        global $DIC;
        $ilDB = $DIC->database();

        if( $ilDB->tableExists('ev2lrs_queue') ) {
            $ilDB->dropTable('ev2lrs_queue');
        }
        if( $ilDB->tableExists('ev2lrs_queue_seq') ) {
            $ilDB->dropTable('ev2lrs_queue_seq');
        }

    }

    /**
     * @param string $component
     * @param string $event
     * @param array $parameters
     * @throws Exception
     */
    public function handleEvent(string $component, string $event, array $parameters)
    {

        global $DIC; /** @var Container $DIC */

        $this->dic = $this->dic ?? $DIC;

        #$this->dic->logger()->root()->info('#################### Events2Lrs handleEvent');

        if(in_array($event, $this->allowedEvents)) {

            $parameters['event'] = $event;

            $parameters['component'] = $component;

            $parameters['usr_id'] = $parameters['usr_id'] ?? $parameters['user_id'] ?? 0;

            unset($parameters['user_id']);

            if (!isset($parameters['obj_id']) && isset($parameters['ref_id'])) {

                $parameters['obj_id'] = ilObject::_lookupObjId($parameters['ref_id']);

            }

            if (!isset($parameters['ref_id']) && null !== ($refId = $this->getRefForObjId($parameters['obj_id'] ?? 0))) {

                $parameters['ref_id'] = $refId;

            }

            switch(true) {

                case !$this->hasLrsType():

                case empty($parameters['ref_id']):

                case empty($parameters['obj_id']):

                case empty($parameters['usr_id']):

                case !$this->privacyAllowed($parameters):

                case $this->storedParams[$event] === $parameters:

                    $this->dic->logger()->root()->info('#################### Events2Lrs handleEvent break @LINE: ' . __LINE__);

                    break;


                default:

                    $this->storedParams[$event] = $parameters;

                    $parameters['date'] = date('Y-m-d H:i:s');

                    if (null !== $queueId = $this->addInitialDbEntrty($parameters)) {

                        $this->dic->logger()->root()->info('#################### Events2Lrs handleEvent addInitialDbEntrty: ' . $queueId);

                        $ns = implode('\\', [
                            'ILIAS\Plugin\Events2Lrs',
                            'Event',
                            str_replace('/', '\\', $component),
                            ucfirst($event)
                        ]);

                        $this->execTask($ns, $queueId);

                    }

                    break;
            }

        }
    }


    public function execTask(string $ns, int $queueId) : bool
    {
        if(array_key_exists($ns, $this->taskExec)) {

            return true;

        } else {

            $this->taskExec[$ns] = microtime();

            try {

                #self::endpointCall(Events2LrsRouterGUI::EXEC_BT, ['queue_id' => $queueId, 'event' => $ns]);

                /** @var H5P|SendAllStatements|UpdateStatus $ns */
                new $ns($queueId);

            } catch (Exception $e) {

                unset($this->taskExec[$ns]);

                $this->dic->logger()->root()->dump($e);

            }

        }

        return true;
    }


    public function privacyAllowed(array $param) : bool
    {
        /** @var null|ilLp2LrsPrivacyPlugin $xlpp */
        $xlpp = null;

        if(!(ilPluginAdmin::getAllPlugins()['xlpp'] ?? false)) {

            $this->dic->logger()->root()->debug('LpPrivacy-Plugin is not installed');

            return true;

        }

        try {

            if($xlpp = ilPluginAdmin::getPluginObjectById('xlpp')) {

                $this->dic->logger()->root()->debug('LpPrivacy-Plugin detected');

            }

        } catch(Exception $e) {

            $this->dic->logger()->root()->dump($e);

            return true;

        }

        #if((int)$xlpp->getVersion() === (int)$xlpp->getLastUpdateVersion()) {

        if($xlpp->getActive()) {

            $this->dic->logger()->root()->debug('LpPrivacy-Plugin is active');

            $tree = array_reverse($this->dic->repositoryTree()->getPathFull($param['ref_id']));

            foreach($tree as $node) {

                if ($node['type'] === 'crs') {

                    if( $xlpp->getConfig()->getCheck('lp2lrscy_' . $node['ref_id'] . '_' . $param['usr_id'] ) ) {

                        return true;

                    } else {

                        $this->dic->logger()->root()->debug('LpPrivacy-Status: NOT allowed');

                        return false;

                    }

                }

            }

        } else {

            $this->dic->logger()->root()->debug('LpPrivacy-Plugin is installed but not active');

            return true;

        }

        return false;

    }


    public function getRefForObjId($objId) : ?int
    {
        $refs = ilObject::_getAllReferences($objId) ?? [null];

        $refId = array_pop($refs);

        return $refId;
    }

    /**
     * @throws Exception
     */
    public static function getDefaultEvents() : array
    {
        global $DIC; /** @var Container $DIC */

        /*
        $jsonFileContent =  $DIC->refinery()->to()->string()->transform(
            file_get_contents( dirname(__DIR__, 1) . '/plugin.ini.json')
        );
*/
        $jsonFileContent =  file_get_contents( dirname(__DIR__, 1) . '/plugin.ini.json');

        $configParam = json_decode($jsonFileContent, 1);

        return $configParam['defaultEvents'] ?? [];
    }

    public static function getSelectedEvents()
    {
        $instance = new self();

        return json_decode($instance->settings->get($instance->getId() . '__events', 0));

    }


    public static function getUntrackedVerbs() : array
    {
        $instance = new self();

        $untrackedVerbs = $instance->settings->get(self::PLUGIN_ID . '__untracked_verbs', 0);

        return !is_int($untrackedVerbs) ? json_decode($untrackedVerbs, 1) : [];

    }


    public static function getAllVerbs(bool $removePrefix = true) : array
    {
        $defaultVerbList = ilCmiXapiVerbList::getInstance()->getSelectOptions();

        array_shift($defaultVerbList);

        $verbList = $defaultVerbList;

        if((int)ILIAS_VERSION > 6 && $removePrefix) {

            foreach (($defaultVerbList) as $key => $verb) {

                $verbPart = explode('_', $verb);

                $verbList[$key] = substr(array_pop($verbPart), 0);
            }

        }

        return $verbList;

    }


    public static function endpointCall(string $action, array $param = [], string $method = 'POST', bool $returnTransfer = true, bool $async = true,  string $contentType = 'application/json;charset=UTF-8') : ?string
    {

        $url = ILIAS_HTTP_PATH . '/' . Events2LrsRouterGUI::getUrl($action);

        $isPost = in_array(strtoupper($method), ['POST', 'PUT']);

        #$timeout = 60;
        $maxRedirects = 10;

        $header = [
            'content-type: ' . ($isPost ? 'application/x-www-form-urlencoded' : $contentType),
            'accept: application/json, text/plain, */*',
        ];

        try {

            $curl = new ilCurlConnection($url);

            $curl->init();

            #0 === strpos(ILIAS_VERSION, 5) ? $curl->init() : $curl->init(false);

            $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
            $curl->setOpt(CURLOPT_SSL_VERIFYHOST, false);
            $curl->setOpt(CURLOPT_COOKIE, json_encode($_COOKIE));

            #$curl->setOpt(CURLOPT_CONNECTTIMEOUT, $timeout);
            $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
            $curl->setOpt(CURLOPT_MAXREDIRS, $maxRedirects);
            $curl->setOpt(CURLOPT_HTTPHEADER, $header);
            $curl->setOpt(CURLOPT_CUSTOMREQUEST, strtoupper($method));
            if( $isPost ) {
                $curl->setOpt(CURLOPT_POST, 1);
                $curl->setOpt(CURLOPT_POSTFIELDS, http_build_query($param));
            }
            $curl->setOpt(CURLOPT_RETURNTRANSFER, $returnTransfer);

            $response = $curl->exec();

            if(!$returnTransfer) {

                return null;

            }

            #echo '<pre>'; var_dump($response); exit;
            $code = (int)$curl->getInfo(CURLINFO_HTTP_CODE);

            $json = json_decode($response, true);

            $json['http_code'] = $code;

            $json['called_param'] = $param;

            if( strlen($json['error']) && substr($json['error'], -1) !== '.' ) {

                $json['error'] .= '.';

            }

            $json['called_endpoint'] = $url;

            $json['called_method'] = $method;

        } catch (ilCurlConnectionException $e) {

            $json = [
                'success' => false,
                'error' => $e->getMessage()
            ];

            if( (bool)strlen($json['error']) ) {

                $instance = new self();

                $instance->dic->logger()->root()->dump($json);

            }

        }

        $instance = new self();

        $instance->dic->logger()->root()->dump($json);

        return json_encode($json);

    }


}
