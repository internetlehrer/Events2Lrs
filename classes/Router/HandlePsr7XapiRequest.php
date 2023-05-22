<?php
namespace ILIAS\Plugins\Events2Lrs\Router;
/**
 * Handle Client Requests
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */


use Events2LrsRouterGUI;
use Exception;
use ilEvents2LrsPlugin;
use ILIAS\DI\Container;
use ILIAS\HTTP\Response\Sender\ResponseSendingException;
use ilObject;
use ilObjectFactory;
use ilPlugin;
use ilTemplate;

#use ilEvents2LrsPlugin;


/**
 * @property $storedParams
 */
class HandlePsr7XapiRequest
{
    CONST LOAD_JS_QUERY_CMD = ['showContents', 'view', 'resume', 'layout', 'ilObjLearningModule'];

    CONST XAPI_PSR7_REQUEST_HEADER = 'xAPI';


    /**
     * @var null|self
     */
    private static $instance = null;
    /**
     * @var Container
     */
    protected $dic;

    /**
     * @var int
     */
    protected $refId;

    /**
     * @var int
     */
    protected $objId;

    /**
     * @var int
     */
    protected $usrId;

    /**
     * @var ilPlugin
     */
    protected $parentObj;

    /**
     * @var bool
     */
    protected $ishandleXapiRequest;
    /**
     * @var \GuzzleHttp\Psr7\ServerRequest|\Psr\Http\Message\ServerRequestInterface
     */
    private $request;


    /**
     * @param ilEvents2LrsPlugin $parentObj
     */
    public function __construct(ilEvents2LrsPlugin $parentObj)
    {
        global $DIC; /** @var Container $DIC */

        $this->dic = $DIC;

        $this->request = \GuzzleHttp\Psr7\ServerRequest::fromGlobals();

        $this->parentObj = $parentObj;

        if ($this->isH5PObjGuiRequest()) {

            if (!$this->isXapiRequest()) {

                $this->modifyH5PObjGuiResponse();

            }

        } else {

            #$this->modifyResponseSendAllStatements();

        }


    }


    private function isH5PObjGuiRequest() : bool
    {
        $this->refId = (int)(filter_var($this->request->getQueryParams()['ref_id'], FILTER_SANITIZE_NUMBER_INT) ?? 0);

        $cmd = filter_var($this->request->getQueryParams()['cmd'], FILTER_SANITIZE_STRING) ?? null;

        $target = filter_var($this->request->getQueryParams()['target'], FILTER_SANITIZE_STRING) ?? false;

        if(!$this->refId && $target) {

            $idsArr = explode('_', $target);

            $this->refId =  (int)array_pop($idsArr);

        }

        if($this->refId && !$cmd) {

            try {

                /** @var ilObject $obj */
                $obj = ilObjectFactory::getInstanceByRefId($this->refId);

                $cmd = get_class($obj);

            } catch (Exception $e) {

                return false;

            }

        }

        if($this->refId && in_array($cmd, self::LOAD_JS_QUERY_CMD)) {

            return true;

        }

        return false;

    }

    protected function isXapiRequest() : bool
    {
        $hasHeader = $this->request->hasHeader(self::XAPI_PSR7_REQUEST_HEADER);

        if($hasHeader) {

            return true;

        }

        return false;

    }

    private function modifyH5PObjGuiResponse() : void
    {

        $urlParts = parse_url(ILIAS_HTTP_PATH . '/' . Events2LrsRouterGUI::getUrl());

        /** @var array $queryParam */
        parse_str($urlParts['query'], $queryParam);

        $cmdClassParts = explode('\\', $queryParam['cmdClass']);

        $queryParam['cmdClass'] = array_pop($cmdClassParts); # ('events2lrsroutergui');

        $urlRouterQuery = http_build_query($queryParam);

        $urlRouter = ILIAS_HTTP_PATH . '/ilias.php?' . $urlRouterQuery;

        $iliasPath = ILIAS_HTTP_PATH;

        $scriptPath = str_replace(ILIAS_ABSOLUTE_PATH, '', dirname(__DIR__, 2)) . '/src/js/';

        $iliasHttpScriptPath = ILIAS_HTTP_PATH . $scriptPath;

        $initEvents2LrsJs = $iliasHttpScriptPath . 'init.Events2Lrs.js';

        $initH5PJs = $iliasHttpScriptPath . 'init.H5P.js';

        // todo check ui template fix and remove 1 === 2 &&
        if(1 === 2 && isset($GLOBALS['tpl']) && $this->dic->offsetExists('tpl')) {

            /** @var \ilGlobalTemplate $ilTpl */
            global $ilTpl;

            $tpl = $this->dic['tpl'];

            $tpl->addOnLoadCode('let urlEvents2LrsRouterGUI = "' . $urlRouter . '";');

            $tpl->addJavaScript(ILIAS_HTTP_PATH .
                str_replace(ILIAS_ABSOLUTE_PATH, '', dirname(__DIR__, 2)) .
                '/src/js/h5p_ilXaaS.js');

        } else {

            echo <<<HEREDOC
<script src="$initEvents2LrsJs"></script>
<script>
    Events2Lrs = $.extend(Events2Lrs, {
        iliasHttpPath: "$iliasPath",
        pluginScriptPath: "$scriptPath",
        urlRouterGUI: "$urlRouter"
    });
    
    Events2Lrs.getScript('init.H5P.js');
</script>
HEREDOC;
            
            #echo '<script>if(undefined === window.urlEvents2LrsRouterGUI) {window.urlEvents2LrsRouterGUI = "' . $urlRouter . '";}</script>';
/*
            echo '<script src="' . ILIAS_HTTP_PATH .
                $scriptPath .
                'h5p_ilXaaS.js"></script>';*/

        }

    }


    private function modifyResponseSendAllStatements() : void
    {
        $urlParts = parse_url(ILIAS_HTTP_PATH . '/' . Events2LrsRouterGUI::getUrl('sendAllStatements'));

        /** @var array $queryParam */
        parse_str($urlParts['query'], $queryParam);

        $cmdClassParts = explode('\\', $queryParam['cmdClass']);

        $queryParam['cmdClass'] = array_pop($cmdClassParts);

        $urlRouterQuery = http_build_query($queryParam);

        $urlRouter = ILIAS_HTTP_PATH . '/ilias.php?' . $urlRouterQuery;
if(!$this->dic->http()->request()->hasHeader('X-Requested-With')) {
    echo <<<HEREDOC
<script>
(function ($) {

    $(window).one('load', function (e) {
        $.ajax({
            type: 'GET',
            async: true,
            url: "$urlRouter"
        });
    });
})(jQuery);
</script>
HEREDOC;
}
    }

    public static function fixUITemplateInCronContext() : void
    {
        global $DIC; /** @var Container */

        // Fix missing tpl ui in cron context used in some core object constructor
        if ($DIC->offsetExists("tpl")) {
            if (!isset($GLOBALS["tpl"])) {
                $GLOBALS["tpl"] = $DIC->ui()->mainTemplate();
            }
        } else {
            if (!isset($GLOBALS["tpl"])) {
                $GLOBALS["tpl"] = new class() extends ilTemplate {

                    /**
                     * @inheritDoc
                     */
                    public function __construct()
                    {
                        #parent::__construct();
                    }
                };
            }

            $DIC->offsetSet("tpl", $GLOBALS["tpl"]);
        }
    }




}



