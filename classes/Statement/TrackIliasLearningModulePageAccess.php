<?php
namespace ILIAS\Plugin\Events2Lrs\Statement;

use ilCmiXapiDateTime;
use ilCmiXapiLrsType;
use ILIAS\Plugin\Events2Lrs\Xapi\Statement\XapiStatement;
use ilLink;
use ilObject;
use ilObjUser;


class TrackIliasLearningModulePageAccess extends AbstractStatement
{
    public function __construct(ilCmiXapiLrsType $lrsType, array $eventParam = [])
    {
        $eventParam['event'] = 'trackIliasLearningModulePageAccess';

        parent::__construct($lrsType, $eventParam);
    }

    public function getObjectPermaLink(ilObject $object, bool $withObjId = true): string
    {
        if($object->getType() !== 'lm') {

            return parent::getObjectPermaLink($object, $withObjId);

        }

        $stringIds = '';

        $objId = $object->getId();

        if($withObjId) {

            $stringIds .= "&obj_id_lrs=$objId";

        }

        $targetRef = $this->eventParam['pg_id'] . '_' . $this->eventParam['ref_id'];

        return ilLink::_getLink($targetRef, 'pg') .  $stringIds;

    }

    public function getObjectProperties(ilObject $object): array
    {
		$name = $object->getTitle();
		if($object->getType() == 'lm') {
			$name = $object->getTitle() . $this->getLMDataTitle();
		}
        $objectProperties = [
            #'id' => $this->getObjectId($object),
            'id' => $this->getObjectPermaLink($object),
            'definition' => [
                'name' => [$this->getLocale() => $name],
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

    private function getLMDataTitle() : string
    {
        $db = $this->dic->database();

        $sql = "SELECT title FROM lm_data WHERE obj_id = " . $db->quote($this->eventParam['pg_id'], 'integer') .
            " AND lm_id = " . $db->quote($this->eventParam['obj_id'], 'integer');

        $res = $db->query($sql);

        $row = $db->fetchAssoc($res);

        return ($row['title'] ?? false) ? ': ' . $row['title'] : '';
    }
}