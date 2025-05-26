<?php
namespace ILIAS\Plugin\Events2Lrs\Xapi\Statement;


/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */
/**
 * Class ilev2lrsPlugin
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 */

use Closure;
use Exception;
use ilCmiXapiDateTime;
use ilCmiXapiLrsType;
use ilCmiXapiUser;
use ilDatabaseException;
use ilDateTimeException;
use ilEvents2LrsPlugin;
use ILIAS\DI\Container;
use ilLink;
use ilLogger;
use ilLoggerFactory;
use ilLPStatus;
use ilMD;
use ilObject;
use ilObjectFactory;
use ilObjectNotFoundException;
use ilObjUser;
use ilPlugin;
use ilPluginException;
use JsonSerializable;


class XapiStatement implements JsonSerializable, XapiStatementInterface
{
    CONST EVENT_STATUS_VERBID = [
        'afterChangeEvent' => ['http://adlnet.gov/expapi/verbs/experienced'],
        'readCounterChange' => ['http://adlnet.gov/expapi/verbs/experienced'],
        'updateStatus' => [
            ilLPStatus::LP_STATUS_FAILED_NUM => 'http://adlnet.gov/expapi/verbs/failed',
            ilLPStatus::LP_STATUS_COMPLETED_NUM => 'http://adlnet.gov/expapi/verbs/completed',
            ilLPStatus::LP_STATUS_IN_PROGRESS_NUM => 'http://adlnet.gov/expapi/verbs/attempted',
            ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM => 'http://adlnet.gov/expapi/verbs/attempted'
        ],
        'H5P' => ['http://adlnet.gov/expapi/verbs/interacted'],
        'uiEvent' => ['http://adlnet.gov/expapi/verbs/interacted'],
        'trackIliasLearningModulePageAccess' => ['http://adlnet.gov/expapi/verbs/experienced'],
        'startTestPass' => ['http://adlnet.gov/expapi/verbs/attempted'],
        'resumeTestPass' => ['http://adlnet.gov/expapi/verbs/resumed'],
        'suspendTestPass' => ['http://adlnet.gov/expapi/verbs/suspended'],
        'finishTestPass' => ['http://adlnet.gov/expapi/verbs/completed'],
        'finishTestResponse' => ['http://adlnet.gov/expapi/verbs/answered']
    ];

	protected static $XAPI_VERBS = [
		'http://adlnet.gov/expapi/verbs/failed' => 'failed',
		'http://adlnet.gov/expapi/verbs/completed' => 'completed',
		'http://adlnet.gov/expapi/verbs/attempted' => 'attempted',
        'http://adlnet.gov/expapi/verbs/experienced' => 'experienced',
        'http://adlnet.gov/expapi/verbs/interacted' => 'interacted',
        'http://adlnet.gov/expapi/verbs/suspended' => 'suspended',
        'http://adlnet.gov/expapi/verbs/resumed' => 'resumed',
        'http://adlnet.gov/expapi/verbs/answered' => 'answered'
	];

	protected static $RELEVANT_PARENTS = ['cat', 'crs', 'grp', 'root'];
	
	const CATEGORY_DEFINITION_TYPE_TAG = 'http://id.tincanapi.com/activitytype/tag';
	
	const DEFAULT_LOCALE = 'en-US';

    /**
     * @var ilEvents2LrsPlugin
     */
    public $plugin;

    /**
	 * @var ilCmiXapiLrsType
	 */
    protected $lrsType;
	
	/**
	 * @var ilObject
	 */
	protected $object;
	
	/**
	 * @var ilObjUser
	 */
	protected $user;
	
	/**
	 * @var ilCmiXapiDateTime
	 */
	public $xapiTimestamp;
	
	/**
	 * @var int
	 */
	public $lpStatus;
	
	/**
	 * @var int
	 */
    public $percentage;

    /**
     * @var array $eventParam
     */
    public $eventParam;

    /** @var Container $dic */
    protected $dic;

    protected $request;

    /**
     * @var string
     */
    protected $event;

    /**
     * @var int
     */
    protected $refId;

    /**
     * @var int
     */
    protected $userId;

    /**
     * @var ilLogger
     */
    public $logger;

    /**
     * XapiStatement constructor.
     *
     * Additional key/value pairs the $param[] can be set for own usage.
     *
     * Required in $param[]:
     * obj_id : int, ref_id : int, usr_id : int, event : string
     *
     * Optional in $param[]:
     * date : string (default Y-m-d H:i:s), status : int (default 0), percentage : int (default 0)
     *
     * @param ilCmiXapiLrsType $lrsType
     * @param array $param
     * @throws ilDatabaseException
     * @throws ilDateTimeException
     * @throws ilObjectNotFoundException
     * @throws ilPluginException
     */
	public function __construct(ilCmiXapiLrsType $lrsType, array $param /*, string $event = ''*/)
	{
        global $DIC; /** @var Container $DIC */

        $this->dic = $DIC;

        $this->request = $this->dic->http()->request();

        $this->logger = $this->dic->logger()->root();

        $this->plugin = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Cron', 'crnhk', 'Events2Lrs');

		$this->lrsType = $lrsType;

        $this->refId = $param['ref_id'];

        $this->userId = $param['usr_id'];

        $this->object = ilObjectFactory::getInstanceByRefId($this->refId, false);

		$this->user = ilObjectFactory::getInstanceByObjId($this->userId, false);

		$this->xapiTimestamp = new ilCmiXapiDateTime($param['date'] ?? date('Y-m-d H:i:s'), IL_CAL_DATETIME);

		$this->lpStatus = $param['status'] ?? 0;

		$this->percentage = $param['percentage'] ?? 0;

        $this->event = $param['event'];

        if(isset($param['usr_id'])) {

            unset($param['usr_id']);

        }

        if(isset($param['date'])) {

            unset($param['date']);

        }

        if(isset($param['changeProp']['read_count'])) {

            unset($param['changeProp']['read_count']);

        }

        $this->eventParam = $param;

	}

    /**
     * @return string
     * @throws Exception
     */
	public function buildTimestamp() : string
    {
		return $this->xapiTimestamp->toXapiTimestamp();
	}
	
	/**
	 * @return array
	 */
	public function buildActor(): array
    {
		if(isset(array_flip(get_class_methods($this->lrsType))['getPrivacyName'])) //ILIAS 7
        {
            $identMode = $this->lrsType->getPrivacyIdent();
			$nameMode = $this->lrsType->getPrivacyName();
		} else {
			$identMode = $this->lrsType->getUserIdent();
			$nameMode = $this->lrsType->getUserName();
		}
        try {
            return [
                'objectType' => 'Agent',
                #'mbox' => 'mailto:'.ilCmiXapiUser::getIdent($identMode ,$this->user),
                'account' => [
                    'homePage' => 'http://' . $_SERVER['HTTP_HOST'],
                    'name' => ilCmiXapiUser::getIdent($identMode ,$this->user)
                ],
                'name' => ilCmiXapiUser::getName($nameMode ,$this->user)
            ];
        } catch (Exception $e) {
            return [
                'objectType' => 'Agent',
                'account' => [
                    'homePage' => 'http://' . $_SERVER['HTTP_HOST'],
                    'name' => 'unknown_or_deleted@example.com'
                ],
                'name' => 'unknown_or_deleted'
            ];
        }
	}
	
	/**
	 * @return array
	 */
	public function buildVerb(): array
    {
		return [
			'id' => $this->getVerbId(),
			'display' => [
                $this->getLocale() => $this->getVerbName()
            ]
		];
	}
	

	/**
	 * @return array
	 */
	public function buildResult() : ?array
    {

        return null;

	}


	/**
	 * @return array
	 */
	public function buildObject() : array
    {

		return $this->getObjectProperties($this->object);

	}

    /**
     * @return array
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
	public function buildContext() : array
    {
		$context = [
			'contextActivities' => []
		];
		
		$parent = $this->getContextParent($this->object);
		
		if( $parent )
		{
			$context['contextActivities']['parent'] = $this->getObjectProperties($parent);

            $context['contextActivities']['grouping'] = array_map(
                function($node) : array {
                    $pObj = ilObjectFactory::getInstanceByRefId($node['child']);
                    return $this->getObjectProperties($pObj);
                },
                $this->dic->repositoryTree()->getNodePath($parent->getRefId())
                #$this->dic->repositoryTree()->getNodePath($this->object->getRefId())
            );

            $lastKey = count($context['contextActivities']['grouping']);

            $lastKey--;

            if($this->object->getType() === 'lm') {

                $permaLink = $this->getObjectPermaLink($this->object);

                if(substr_count($permaLink, 'target=pg_')) {

                    $context['contextActivities']['parent']['id'] = preg_replace('%(pg_[\d]{1,}_)%', 'lm_', $permaLink);

                    $langKey = array_key_last($context['contextActivities']['parent']['definition']['name']);

                    $context['contextActivities']['parent']['definition']['name'][$langKey] = ilObject::_lookupTitle($this->object->getId());

                }

            }

            if($context['contextActivities']['grouping'][$lastKey]['id'] === $context['contextActivities']['parent']['id']) {

                //
                if(!in_array($this->getVerbName(), ['answered'])) {

                    unset($context['contextActivities']['grouping'][$lastKey]);

                }

            }

            $context['extensions'] = [
                "http://ilias.event" => $this->eventParam['event'],
                "http://ilias.version" => ILIAS_VERSION,
                "http://ilias.plugin" => $this->plugin->getPluginName(),
                "http://ilias." . $this->plugin->getPluginName() => $this->plugin->getVersion(),
                "https://w3id.org/xapi/lms/extensions/sessionid" => hash('sha256', session_id()),
            ];

            if('H5P' === $this->event && ($addExtension = $this->dic->http()->request()->getParsedBody()['context']['contextActivities']['category'][0]['id'] ?? null)) {

                $parsedIdentifier = parse_url($addExtension);

                $parsedPath = explode('/', $parsedIdentifier['path']);

                $extValue = array_pop($parsedPath);

                $extId = $parsedIdentifier['scheme'] . '://' . $parsedIdentifier['host'] . implode('/', $parsedPath);

                $context['extensions'][$extId] = $extValue;

            }

            foreach(array_keys($context['extensions']) as $extKey) {

                $newExtKey = str_replace('.', '&46;', $extKey);

                $context['extensions'][$newExtKey] = $context['extensions'][$extKey];

                unset($context['extensions'][$extKey]);

            }

		}


        $categories = $this->getObjectCategories($this->object);

		if( $categories )
		{
            $context['contextActivities']['category'] = $categories;
        }
		
		return $context;
	}

    /**
     * @return array
     * @throws Exception
     */
	public function jsonSerialize(): array
    {
		$statement = [];
		
		$statement['timestamp'] = $this->buildTimestamp();
		
		$statement['actor'] = $this->buildActor();
		
		$statement['verb'] = $this->buildVerb();
		
		if( $result = $this->buildResult() ?? false )
		{
			$statement['result'] = $result;
		}
		
		$statement['object'] = $this->buildObject();
		
		$statement['context'] = $this->buildContext();
		
		return $statement;
	}
	
	/**
	 * @return string
	 */
	public function getVerbId(): string
    {
        return self::EVENT_STATUS_VERBID[$this->event][$this->lpStatus];
	}
	
	/**
	 * @return string
	 */
	public function getVerbName()
	{
        return self::$XAPI_VERBS[$this->getVerbId()];
    }
	
	/**
	 * @return float
	 */
	public function getScore()
	{
		return $this->percentage / 100;
	}
	
	/**
	 * @return string
	 */
	public function getObjectType(ilObject $object)
	{
		switch( $object->getType() )
		{
			case 'cat':
			case 'crs':
			case 'grp':
			case 'fold':
            case 'root':
				return 'Group';
			
			default:
				return 'Activity';
		}
	}

    /**
     * @param ilObject $object
     * @return string
     */
	public function getObjectId(ilObject $object): string
    {
		switch( $object->getType() )
		{
			case 'cmix':
				
				/* @var ilObjCmiXapi $object */
				
				if( strlen($object->getActivityId()) )
				{
					return $object->getActivityId();
				}
				else
				{
					return $this->getFallbackObjectId($object);
				}
				
			case 'lti':
				
				/* @var ilObjLTIConsumer $object */

				if( $object->getUseXapi() && strlen($object->getActivityId()) )
				{
					return $object->getActivityId();
				}
				else
				{
					return $this->getFallbackObjectId($object);
				}
				
			default:
				
				return $this->getFallbackObjectId($object);
		}
	}

    /**
     * @param ilObject $object
     * @return string
     */
	public function getFallbackObjectId(ilObject $object): string
    {
		$settings = new ilSetting('cmix');
		$iliasUid = $settings->get('ilias_uuid');
		
		return 'http://ilias.local/'.$iliasUid.'/'.$object->getId();
	}

    /**
     * @param ilObject $object
     * @return string
     */
	public function getObjectDefinitionType(ilObject $object): string
    {
		switch($object->getType())
		{
            case 'root':
			case 'cat':
				
				return 'http://id.tincanapi.com/activitytype/category';
				
			case 'crs':
			case 'grp':
				
				return 'http://adlnet.gov/expapi/activities/course';
		}
		
		return 'http://adlnet.gov/expapi/activities/module';
	}

    /**
     * @param ilObject $object
     * @param bool $withObjId
     * @return string
     */
	public function getObjectPermaLink(ilObject $object, bool $withObjId = true): string
    {
        $stringIds = '';

        $objId = preg_replace('%([\D].*)%', '', $object->getId());

        if($withObjId) {

            $stringIds .= "&obj_id_lrs=$objId";

        }

		return ilLink::_getLink($object->getRefId(), $object->getType()) .
            $stringIds; #($withObjId ? "&obj_id_lrs=$objId" : ''); # !!! &obj_id_lrs=$objId equals queried regex ^.+&obj_id_lrs=uid$
	}
	
	public function getObjectProperties(ilObject $object): array
    {
		$objectProperties = [
			#'id' => $this->getObjectId($object),
            'id' => $this->getObjectPermaLink($object),
            'definition' => [
                'name' => [$this->getLocale() => $object->getTitle()],
                'type' => $this->getObjectDefinitionType($object)
            ]
		];

        if( $object->getDescription() != '')
        {
            $objectProperties['definition']['description'] = [$this->getLocale() => $object->getDescription()];
        }

        /*
		if( $object->getRefId() )
		{
			$objectProperties['definition']['moreInfo'] = $this->getObjectMoreInfo($object);
		}
		*/

		return $objectProperties;
	}
	
	/**
	 * @param ilObject $object
	 * @return bool|ilObject|object|null
	 */
	public function getContextParent(ilObject $object)
	{
        global $DIC; /** @var \ILIAS\DI\Container */
		
		if( !$object->getRefId() )
		{
			return null;
		}

		$parents = self::$RELEVANT_PARENTS;
		if( $object->getType() == 'crs' )
        {
            $parents = ['cat', 'root'];
        }

		$pathNodes = array_reverse($DIC->repositoryTree()->getPathFull($object->getRefId()));
		
		foreach($pathNodes as $nodeData)
		{
			if( !in_array($nodeData['type'], $parents) )
			{
				continue;
			}
			
			return ilObjectFactory::getInstanceByRefId($nodeData['ref_id'], false);
		}
		
		return null;
	}
	
	/**
	 * @param ilObject $object
	 * @return array
	 */
	public function getObjectCategories(ilObject $object)
	{
		$categories = [];
		
		foreach($this->getKeywords($object) as $keyword)
		{
			$categories[] = [
				'id' => 'http://ilias.local/keyword/'.rawurlencode($keyword),
				'definition' => [
				    'name' => [$this->getLocale() => $keyword],
					'type' => self::CATEGORY_DEFINITION_TYPE_TAG
				]
			];
		}
		
		return $categories;
	}
	
	/**
	 * @param ilObject $object
	 * @return array
	 */
	public function getKeywords(ilObject $object)
	{
		$keywords = [];
		
		$metadata = new ilMD($object->getId(), $object->getId(), $object->getType());
		
		if( !$metadata->getGeneral() )
		{
			ilLoggerFactory::getRootLogger()->debug(
				'No keywords found for object '.$object->getType().$object->getId()
			);
			
			return $keywords;
		}
		
		foreach($metadata->getGeneral()->getKeywordIds() as $keywordId)
		{
		    if ($metadata->getGeneral()->getKeyword($keywordId)->getKeyword() != "") {
                $keywords[] = $metadata->getGeneral()->getKeyword($keywordId)->getKeyword();
            }
		}
		
		ilLoggerFactory::getRootLogger()->debug(
			'Found keywords for object '.$object->getType().$object->getId()."\n".implode(',', $keywords)
		);
		
		
		return $keywords;
	}
	
	public function getLocale()
	{
		global $DIC; /* @var \ILIAS\DI\Container $DIC */
		
		$ilLocale = $DIC->settings()->get('locale', '');
		
		if( strlen($ilLocale) )
		{
			return str_replace('_', '-', $ilLocale);
		}
		
		return self::DEFAULT_LOCALE;
	}
}
