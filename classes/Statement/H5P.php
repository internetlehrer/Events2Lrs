<?php
namespace ILIAS\Plugin\Events2Lrs\Statement;

use Exception;
use ilCmiXapiDateTime;
use ilCmiXapiLrsType;
use ilDatabaseException;
use ILIAS\Plugin\Events2Lrs\Xapi\Statement\XapiStatement;
use ilLink;
use ilObject;
use ilObjectNotFoundException;
use ilObjUser;
use stdClass;


class H5P extends AbstractStatement
{
    CONST H5P_OBJ_DEF_EXT_ID = [
        'prefix' => 'http://h5p.org/x-api/',
        'localContent' => 'h5p-local-content-id',
        'subContent' => 'h5p-subContentId'
    ];

    /**
     * @var array
     */
    protected $queryParams;

    /**
     * @var array
     */
    protected $urlParams;
    /**
     * @var string|null
     */
    protected $localContentId;
    /**
     * @var mixed|null
     */
    protected $localContentName;
    /**
     * @var array|null
     */
    protected $localContentScore;
    /**
     * @var string|null
     */
    protected $subContentId;
    /**
     * @var string|null
     */
    protected $subContentTitle;
    /**
     * @var array|null
     */
    protected $dropInParam;
    /**
     * @var array
     */
    private $statement;


    public function __construct(ilCmiXapiLrsType $lrsType, array $eventParam = [])
    {

        $eventParam['event'] = 'H5P';

        parent::__construct($lrsType, $eventParam);

        parse_str(parse_url($this->postParam()['UrlH5PModule'])["query"], $this->urlParams);

        $this->queryParams = $this->postParam()['queryParamH5PModule'];

        $this->dropInParam = json_decode(
            json_encode($this->postParam() ?? []), 1
        );

        $this->localContentId = $this->postParam()['object']['definition']['extensions'][
            self::H5P_OBJ_DEF_EXT_ID['prefix'] . self::H5P_OBJ_DEF_EXT_ID['localContent']
        ] ?? null;

        $this->localContentName = ($localContentName = $this->postParam()['object']['definition']['name'] ?? null)
            ? array_pop($localContentName)
            : null;

        $this->localContentScore = $this->postParam()['result']['score']['scaled'] ?? null;

        $this->subContentId = $this->postParam()['object']['definition']['extensions'][
            self::H5P_OBJ_DEF_EXT_ID['prefix'] . self::H5P_OBJ_DEF_EXT_ID['subContent']
        ] ?? null;

        $this->subContentTitle = $this->postParam()['object']['definition']['description'] ?? null;
        

    }


    public function postParam() : array
    {
        return $this->request->getParsedBody();
    }

    public function buildResult(): ?array
    {
        $result = [];

        $duration = "";

        $response = "";

        $completion = "";

        $success = "";

        if ($score = $this->postParam()['result']['score'] ?? null) {

            $result =
            [
                'score' => [
                    'scaled' => (float) $score['scaled'],
                    'raw' => (float) $score['raw'],
                    'min' => (float) $score['min'],
                    'max' => (float) $score['max']
                ]
            ];
        }

        if ($localContentDuration = $this->postParam()['result']['duration'] ?? null) {

            $result['duration'] = $localContentDuration;

        }

//        if ($response = $this->postParam()['result']['response'] ?? null) {
//
//            $result['response'] = $response;
//
//        }

        if (isset($this->postParam()['result']['response'])) {

            $result['response'] = (string) $this->postParam()['result']['response'];

        }

        if ($completion = $this->postParam()['result']['completion'] ?? null) {

            $result['completion'] = "true" === $completion;

        }

        if ($success = $this->postParam()['result']['success'] ?? null) {

            $result['success'] = "true" === $success;

        }

        return $result;

    }

    public function buildVerb(): array
    {
        $verb = $this->postParam()['verb'];

        return $verb;

    }

    public function getObjectPermaLink(ilObject $object, bool $withObjId = true, ?string $localContentId = null, ?string $subContentId = null): string
    {

        #$this->dic->logger()->root()->dump($this->eventParam);

        if($object->getType() !== 'lm' && $object->getType() !== 'copa' && !($this->eventParam['queryParamH5PModule']['gotoLink'] ?? false)) {

            return parent::getObjectPermaLink($object, $withObjId);

        }

        $stringIds = '';

        $objId = $object->getId();

        if($withObjId) {

            if ($localContentId) {

                $stringIds .=  '&h5p_object_id=' . $localContentId;

            }

            if ($subContentId) {

                $stringIds .=  '&' . self::H5P_OBJ_DEF_EXT_ID['subContent'] . '=' . $subContentId;

            }

            $stringIds .= "&obj_id_lrs=$objId";

        }

        if('illmpresentationgui' === strtolower($this->urlParams['baseClass'])) {

            $lm_id = \ilObjLearningModuleAccess::_getLastAccessedPage($this->urlParams['ref_id'], $this->user->getId());

            $this->urlParams['obj_id'] = $lm_id;

            $targetRef = $lm_id . '_' . $this->urlParams['ref_id'];

            return ilLink::_getLink($targetRef, 'pg') .  $stringIds;

        }

        if('illmpresentationgui' === strtolower($this->urlParams['cmdClass'])) {

            $targetRef = $this->urlParams['obj_id'] . '_' . $this->urlParams['ref_id'];

            return ilLink::_getLink($targetRef, 'pg') .  $stringIds;

        }

        if('lm_' === substr(strtolower($this->urlParams['target']),0,3)) {

            $this->urlParams['ref_id'] = (int) substr($this->urlParams['target'],3);

            $lm_id = \ilObjLearningModuleAccess::_getLastAccessedPage($this->urlParams['ref_id'], $this->user->getId());

            $this->urlParams['obj_id'] = $lm_id;

            $targetRef = $lm_id . '_' . $this->urlParams['ref_id'];

            return ilLink::_getLink($targetRef, 'pg') .  $stringIds;

        }

        if('pg_' === substr(strtolower($this->urlParams['target']),0,3)) {

            $ar = explode('_', $this->urlParams['target']);

            if (is_array($ar) && count($ar) == 3) {
                $this->urlParams['ref_id'] = (int) $ar[2];
                $this->urlParams['obj_id'] = (int) $ar[1];
                $targetRef = (string) $ar[1] . '_' . $ar[2];
                return ilLink::_getLink($targetRef, 'pg') .  $stringIds;
            }

        }

        if('st_' === substr(strtolower($this->urlParams['target']),0,3)) {

            $ar = explode('_', $this->urlParams['target']);

            if (is_array($ar) && count($ar) == 3) {
                $this->urlParams['ref_id'] = (int) $ar[2];
                $lm_id = \ilObjLearningModuleAccess::_getLastAccessedPage($this->urlParams['ref_id'], $this->user->getId());
                $this->urlParams['obj_id'] = $lm_id;
                $targetRef = (string) $lm_id . '_' . $ar[2];
                return ilLink::_getLink($targetRef, 'pg') .  $stringIds;
            }

        }

        if($gotoLink = $this->eventParam['uiEventData']['queryParamH5PModule']['gotoLink'] ?? null) {

            return $gotoLink . $stringIds;

        }

        return ilLink::_getLink($object->getRefId(), $object->getType()) . $stringIds;

    }

    public function getObjectProperties(ilObject $object): array
    {
        $permaLink = $this->getObjectPermaLink($object, true, $this->localContentId, $this->subContentId);

        $name = [];

        $name[] = $object->getTitle(); //evtl. ergaenzen: $object->getType()

        if(in_array($object->getType(), ['lm', 'copa'])) {

            $name[] = $this->getLMDataTitle();

            if ($this->localContentName) {

                $name[] = ' — ' . $this->localContentName;

            }

            if($this->subContentId && $this->subContentTitle) {

                $name[] = ' — ' . array_pop($this->subContentTitle);

            }

            if ($this->localContentScore = $this->postParam()['result']['score']['scaled'] ?? null) {

                $name[] = ' (' . ((float) $this->localContentScore * 100) . '%)';

            }

        }

        $name = implode($name);

//        $permaLink = $this->getObjectPermaLink($object, true, $this->localContentId, $this->subContentId);

        $objectProperties = [
            'id' => $permaLink,
            'definition' => [
                'name' => [$this->getLocale() => $name],
                'type' => $this->getObjectDefinitionType($object)
            ]
        ];

        if( $object->getDescription() != '')
        {
            $objectProperties['definition']['description'] = [$this->getLocale() => $object->getDescription()];
        }

        return $objectProperties; # array_replace($objectProperties, $requestedObjectProperties);
    }


    private function getLMDataTitle() : string
    {
        $db = $this->dic->database();

        $lmId = ilObject::_lookupObjId($this->urlParams['ref_id']);

        $sql = "SELECT title FROM lm_data WHERE obj_id = " . $db->quote($this->urlParams['obj_id'], 'integer') .
            " AND lm_id = " . $db->quote($lmId, 'integer');

        $res = $db->query($sql);

        $row = $db->fetchAssoc($res);

        return ($row['title'] ?? false) ? ': ' . $row['title'] : '';
    }

    /**
     * @return array
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     * @throws Exception
     */
    public function jsonSerialize(): array
    {
        $this->statement = parent::jsonSerialize();

        $this->dropInObjectDefinition()
            ->dropInResult();

        return $this->statement;
    }


    private function validateRequestKeys(array $param) : array
    {
        $validParam = [];

        foreach ($param as $key => $value) {

            $invalidString = $key !== filter_var($key, FILTER_SANITIZE_STRING);

            $invalidInteger = $key !== filter_var($key, FILTER_SANITIZE_NUMBER_INT);

            $invalidUrl = filter_var($key, FILTER_VALIDATE_URL);

            // expects one or more !invalid
            if(!($invalidString && $invalidInteger && $invalidUrl)) {

                if(is_array($value)) {

                    $value = $this->validateRequestKeys($value);

                }

                $validParam[$key] = $value;

            } else {

                $this->dic->logger()->root()->log('############ Events2Lrs error received invalid H5P request key: ' . $key);

            }

        }

        return $validParam;
    }


    private function castDropInObjectDefinition(array $param) : array
    {
        $validParam = [];

        $param = $this->validateRequestKeys($param);

        foreach($param as $key => $value) {

            if(is_array($value)) {

                $value = $this->castDropInObjectDefinition($value);

            } else {

                switch (true) {

                    #case $key === 'extensions':

                    case $key === 'type':

                        $value = preg_replace('%.%', '&46;', filter_var($value, FILTER_SANITIZE_URL), 1);

                        break;

                    case $key = 'interactionType':

                    case in_array(preg_replace("%-[A-Z]{2}$%", '', $key), array_map(function ($lng) {
                        return $lng['title'];
                    }, ilObject::_getObjectsByType("lng"))):

                        $value = $value === filter_var($value, FILTER_SANITIZE_STRING) ? $value : null;

                        break;

                    case $key === 'id':

                    case is_int($key):

                        $value = $value === filter_var($value, FILTER_SANITIZE_NUMBER_INT) ? (int)$value : null;

                        break;

                    default:

                        $value = null;

                }

            }

            if(!is_null($value)) {

                $validParam[$key] = $value;

            } else {

                $this->dic->logger()->root()->log('############ Events2Lrs error can not cast data type for received key: ' . $key);

            }

        }

        return $validParam;

    }


    private function dropInObjectDefinition() : self
    {
        #$objectDefinitions = $this->castDropInObjectDefinition($this->dropInParam['object']['definition'] ?? []);
        $objectDefinitions = $this->validateRequestKeys($this->dropInParam['object']['definition'] ?? []);

        if(count($objectDefinitions)) {

            $objectDefinitionsKeys = array_keys($objectDefinitions);

            array_walk($objectDefinitionsKeys, function ($key) use ($objectDefinitions) {

                if (!($this->statement['object']['definition'][$key] ?? false)) {

                    $this->statement['object']['definition'][$key] = $objectDefinitions[$key];

                }

            });

        }

        return $this;
    }

    private function castDropInResult(array $param) : array
    {
        $validParam = [];

        $param = $this->validateRequestKeys($param);

        foreach($param as $key => $value) {

            if(is_array($value)) {

                $value = $this->castDropInResult($value);

            } else {

                switch (true) {

                    case in_array($key, ['success', 'completion']):

                        $value = "true" === filter_var($value, FILTER_SANITIZE_STRING);

                        break;

                    case $key === 'response':

                        $value = (int)$value === filter_var($value, FILTER_SANITIZE_NUMBER_INT) ? (int)$value : null;

                        break;

                    default:

                        $value = null;

                }

            }

            if(!is_null($value)) {

                $validParam[$key] = $value;

            } else {

                $this->dic->logger()->root()->log('############ Events2Lrs can not cast data type for received key: ' . $key);

            }

        }

        return $validParam;

    }


    private function dropInResult() : self
    {

        $result = $this->castDropInResult(
            $this->dropInParam['result'] ?? []
        );

        #$result = $this->dropInParam['result'];

        if(count($result)) {

            if (!($this->statement['result'] ?? false)) {

                $this->statement['result'] = $result;

                return $this;

            }

            $resultKeys = array_keys($result);

            array_walk($resultKeys, function ($key) use ($result) {

                if (!($this->statement['result'][$key] ?? false)) {

                    $this->statement['result'][$key] = $result[$key];

                }

            });

        }

        return $this;
    }

}