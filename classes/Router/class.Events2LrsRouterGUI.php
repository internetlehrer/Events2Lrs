<?php

#namespace ILIAS\Plugins\Events2Lrs\Router;

require_once __DIR__ . "/../../vendor/autoload.php";


use ILIAS\DI\Container;
#use ilEvents2LrsPlugin;
use ILIAS\Plugin\Events2Lrs\Event as Event;
#use ilObject;
#use ilObjectFactory;
#use ilUIPluginRouterGUI;

/**
 * Class Events2LrsRouterGUI
 *
 * @package           Events2LrsRouterGUI
 *
 * @author            internetlehrer-gmbh.de
 *
 * @ilCtrl_isCalledBy Events2LrsRouterGUI: ilUIPluginRouterGUI
 */
class Events2LrsRouterGUI
{

    const CMD_H5P_ACTION = "h5pAction";
    const GET_PARAM_OBJ_ID = "obj_id";
    const PLUGIN_CLASS_NAME = ilEvents2LrsPlugin::class;

    const EXEC_BT = 'execBT';


    /**
     * @var ilObject
     */
    protected $object;
    /**
     * @var Container
     */
    private $dic;
    /**
     * @var ilLogger
     */
    private $logger;


    /**
     * Events2LrsRouterGUI constructor
     */
    public function __construct()
    {
        global $DIC; /** @var Container $DIC */

        $this->dic = $DIC;

        $this->logger = $this->dic->logger()->root();

    }



    public function executeCommand()/* : void*/
    {

        $next_class = $this->dic->ctrl()->getNextClass($this);

        $cmd = $this->dic->ctrl()->getCmd();

        if($action = $this->dic->http()->request()->getQueryParams()[$cmd] ?? false) {

            #$this->dic->http()->close();

            $this->{$action}();

        }

        exit;

    }


    /**
     * @param string $action
     * @return string
     */
    public static function getUrl(string $action = 'handleEvent') : string
    {
        global $DIC; /** @var Container $DIC */

        $DIC->ctrl()->setParameterByClass(self::class, self::GET_PARAM_OBJ_ID, $DIC->ctrl()->getContextObjId());

        $DIC->ctrl()->setParameterByClass(self::class, self::CMD_H5P_ACTION, $action);

        return $DIC->ctrl()->getLinkTargetByClass([ilUIPluginRouterGUI::class, self::class], self::CMD_H5P_ACTION, "", true, false);

    }


    /**
     * @throws Exception
     * @throws Exception
     */
    public function handleEvent() : bool
    {

        $this->logger->debug('############### InitRouteXapiRequest');

        $events2Lrs = new ilEvents2LrsPlugin();

        $postBody = $this->dic->http()->request()->getParsedBody();

        $receivedVerb = $postBody['verb']['display'] ?? [uniqid()];

        $untrackedVerbs = ilEvents2LrsPlugin::getUntrackedVerbs();

        if(!in_array(array_pop($receivedVerb), $untrackedVerbs)) {

            $urlH5PModule = parse_url($postBody['UrlH5PModule'], PHP_URL_QUERY);



            /** @var array $queryParam */
            parse_str($urlH5PModule, $queryParam);

            $gotoLink = null;

            if($target = $queryParam['target'] ?? null) {

                $ids = explode('_', $target);

                $queryParam['ref_id'] = array_pop($ids);

                $queryParam['gotoLink'] = $postBody['UrlH5PModule'];

            }


            $this->logger->debug('############### InitRouteXapiRequest dump $uiEventData');
            #$this->logger->dump();

            $hasReadAccess = $this->dic->access()->checkAccessOfUser(
                $this->dic->user()->getId(),
                'read', 'read',
                (int)$queryParam['ref_id']
            );

            if($hasReadAccess && $refId = (int)$queryParam['ref_id'] ?? 0) {

                $handleEventParam = [
                    'obj_id' => \ilObject::_lookupObjectId($refId),
                    'ref_id' => $refId,
                    'usr_id' => $this->dic->user()->getId(),
                    'event' => 'H5P'
                ];

                $this->logger->debug('############### InitRouteXapiRequest handleEvent');

                $postBody['queryParamH5PModule'] = $queryParam;

                unset($postBody['UrlH5PModule']);

                $handleEventParam['uiEventData'] = $postBody;

                #$this->logger->debug(print_r($handleEventParam));

                $events2Lrs->handleEvent('Services/Tracking', 'H5P', $handleEventParam);

            }
        }

        return true;

    }


    public function execBT() : bool
    {
         $post = $this->dic->http()->request()->getParsedBody();

        $ns = $post['event'];

        $queueId = $post['queue_id'];

        /** @var Event\Services\Tracking\UiEvent|Event\Services\Tracking\SendAllStatements|Event\Services\Tracking\UpdateStatus $ns */
        new $ns($queueId);

        return true;
    }





}
