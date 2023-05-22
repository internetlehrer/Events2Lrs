<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\Events2Lrs\Model;

use ILIAS\Plugins\Events2Lrs\DI\Container;
use ILIAS\Plugin\Events2Lrs\Xapi\Statement\XapiStatementBuilder;
use ILIAS\BackgroundTasks\Implementation\Bucket\State;
use ilDBConstants;
use function Sabre\HTTP\parseDate;

trait DbEvents2LrsQueue
{
    protected static $DB_TBL_NAME_EVENTS2LRS_QUEUE = 'ev2lrs_queue';

    protected static $DB_TBLS_IL_BT = ['bucket', 'task', 'value_to_task', 'value'];

    protected static $STATE_DELETABLE = -1;

    protected static $STATE_INIT = 0;

    protected static $STATE_TASK_SCHEDULE = 1;

    protected static $STATE_RUNNING = 11;

    protected static $STATE_CRON_EXEC_1 = 2;

    protected static $STATE_CRON_EXEC_2 = 3;

    protected static $STATE_CRON_EXEC_3 = 4;

    protected static $TIME_BEFORE_DELETE = 2;

    protected static $STATE_CRON_FAILED = 5;

    /** @var \ilDBInterface $db */
    protected $db;

    /**
     * @var int|null
     */
    public $queueId;

    /**
     * @var int|null $refId
     */
    public $refId;

    /**
     * @var int|null $objId
     */
    public $objId;

    /**
     * @var int|null $usrId
     */
    public $usrId;

    /**
     * @var string|null $event
     */
    public $event;

    /**
     * @var string|null $date
     */
    public $date;

    /**
     * @var string|null $state
     */
    public $state;

    /**
     * @var int|null $bucketId
     */
    public $bucketId;

    /**
     * @var string|null $dateFailed
     */
    public $dateFailed;

    /**
     * @var string|null $parameter
     */
    public $parameter;

    /**
     * @var string|null $statement
     */
    public $statement;

    /**
     * @var array $queueEntries
     */
    public $queueEntries = [];

    /**
     * @var int $numQueueEntries
     */
    public $numQueueEntries = 0;


    /**
     * @throws \Exception
     */
    public function addInitialDbEntrty(array $param, $setQueueId = true) : ?int
    {
        if(!in_array($param['event'], json_decode($this->settings->get(\ilEvents2LrsPlugin::PLUGIN_ID . '__events', 0), 1))) { # $this->allowedEvents

            return null;

        }

        $param['queue_id'] = $this->dic->database()->nextId('ev2lrs_queue');

        $param['date'] = $param['date'] ?? date('Y-m-d H:i:s');

        $statement = $param['statement'] ?? in_array($param['event'], \ilEvents2LrsPlugin::getDefaultEvents()) ? '' : XapiStatementBuilder::getInstance($param['event'])->buildPostBody($param);

        unset($param['statement']);

        $values = [
            'queue_id' => array('integer', $param['queue_id']),
            'ref_id' => array('integer', $param['ref_id']),
            'obj_id' => array('integer', $param['obj_id']),
            'usr_id' => array('integer', $param['usr_id']),
            'event' => array('text', $param['event']),
            'date' => array('timestamp', $param['date']),
            'state' => array('integer', self::$STATE_INIT),
            'bucket_id' => array('integer', $param['bucket_id'] ?? null),
            'date_failed' => array('timestamp', null),
            'parameter' => array('text', json_encode($param)),
            'statement' => array('text', $statement)
        ];

        if ($this->dic->database()->insert(self::$DB_TBL_NAME_EVENTS2LRS_QUEUE, $values)) {

            return $setQueueId ? $this->queueId = $param['queue_id'] : $param['queue_id'];

        }

        return null;

    }

    public function updateQueueEntryWithBucketId(?int $bucketId = null, ?int $queueId = null) : bool
    {
        $values = [
            'bucket_id' => array('integer', $bucketId ?? $this->bucketId),
        ];

        $where = [
            'queue_id' => array('integer', $queueId ?? $this->queueId),
        ];

        return (bool)$this->dic->database()->update(self::$DB_TBL_NAME_EVENTS2LRS_QUEUE, $values, $where);
    }

    public function updateQueueEntryWithStateScheduledById(?int $queueId = null) : bool
    {
        $values = [
            'state' => array('integer', self::$STATE_TASK_SCHEDULE),
        ];

        $where = [
            'queue_id' => array('integer', $queueId ?? $this->queueId),
        ];

        return (bool)$this->dic->database()->update(self::$DB_TBL_NAME_EVENTS2LRS_QUEUE, $values, $where);
    }

    public function updateQueueEntryWithStateAndFailedDateById(?int $queueId = null, ?int $state = null, ?string $dateFailed = null) : bool
    {
        $values = [
            'state' => array('integer', $state ?? $this->state ?? self::$STATE_TASK_SCHEDULE),
            'date_failed' => array('timestamp', $dateFailed ?? $this->dateFailed ?? date('Y-m-d H:i:s'))
        ];

        $where = [
            'queue_id' => array('integer', $queueId ?? $this->queueId),
        ];

        return (bool)$this->dic->database()->update(self::$DB_TBL_NAME_EVENTS2LRS_QUEUE, $values, $where);
    }

    public function updateQueueWithStateDeletableById(?int $queueId = null) : bool
    {
        $values = [
            'state' => array('integer', self::$STATE_DELETABLE),
        ];

        $where = [
            'queue_id' => array('integer', $queueId ?? $this->queueId),
        ];

        #return (bool)$this->dic->database()->update(self::$DB_TBL_NAME_EVENTS2LRS_QUEUE, $values, $where);
        if($updated = (bool)$this->dic->database()->update(self::$DB_TBL_NAME_EVENTS2LRS_QUEUE, $values, $where)) {
            $this->deleteAllQueueEntriesByStateDeletable();
        }
        return $updated;
    }


    public function updateQueueEntryWithStatementById(string $statement, ?int $queueId = null) : bool
    {
        $values = [
            'statement' => array('text', $statement)
        ];

        $where = [
            'queue_id' => array('integer', $queueId ?? $this->queueId),
        ];

        return (bool)$this->dic->database()->update(self::$DB_TBL_NAME_EVENTS2LRS_QUEUE, $values, $where);
    }


    public function deleteQueueEntryById(?int $queueId = null) : bool
    {
        return (bool)$this->dic->database()->manipulate("DELETE FROM " .
            $this->dic->database()->quoteIdentifier(self::$DB_TBL_NAME_EVENTS2LRS_QUEUE) .
            " WHERE " . $this->dic->database()->quoteIdentifier('queue_id') .
            " = " . $this->dic->database()->quote($queueId ?? $this->queueId ?? 0, 'integer')
        );
    }

    public function deleteQueueEntryAndItsBucketById(?int $queueId = null, ?int $bucketId = null) : bool
    {
        $bucketId = $bucketId ?? $this->getBucketIdFromQueueEntryById($queueId ?? $this->queueId ?? 0);

        if(
            $this->getBtBucketStateById($bucketId) >= State::FINISHED &&
            $this->getBtBucketPercentageById($bucketId) === 100
        ) {

            #$this->deleteAllBtEntriesByBucketId($bucketId);

            $this->deleteQueueEntryById($queueId);
        }

        return true;
    }

    public function deleteAllQueueEntriesByStateDeletable() : bool
    {
        $dateTime = date('Y-m-d H:i:s', time() - self::$TIME_BEFORE_DELETE);
        $sql = "SELECT " . $this->dic->database()->quoteIdentifier('queue_id') .
            " FROM " . $this->dic->database()->quoteIdentifier(self::$DB_TBL_NAME_EVENTS2LRS_QUEUE) .
            " WHERE " . $this->dic->database()->quoteIdentifier('state') .
            " = " . $this->dic->database()->quote(self::$STATE_DELETABLE, 'integer') .
            " AND date <= " . $this->dic->database()->quote($dateTime, 'timestamp')
        ;

        $res = $this->dic->database()->query($sql);

        while($row = $this->dic->database()->fetchAssoc($res)) {

            #$this->deleteQueueEntryAndItsBucketById($row['queue_id']);

            // to only delete ev2lrs_queue
            $this->deleteQueueEntryById($row['queue_id']);

        }

        return true;
    }

    public function deleteAllBtEntriesByBucketId(?int $bucketId = null) : bool
    {
        $ilBt = 'il_bt_';

        foreach(self::$DB_TBLS_IL_BT as $tbl) {

            $field = $tbl === 'bucket' ? 'id' : 'bucket_id';

            $this->dic->database()->manipulate("DELETE FROM " .
                $this->dic->database()->quoteIdentifier($ilBt.$tbl) .
                " WHERE " . $this->dic->database()->quoteIdentifier($field) .
                " = " . $this->dic->database()->quote($bucketId ?? $this->bucketId ?? 0, 'integer')
            );

        }

        return true;
    }


    public function getEventFromQueueEntryById(?int $queueId = null) : ?string
    {
        $sql = "SELECT " . $this->dic->database()->quoteIdentifier('event') .
            " FROM " . $this->dic->database()->quoteIdentifier(self::$DB_TBL_NAME_EVENTS2LRS_QUEUE) .
            " WHERE " . $this->dic->database()->quoteIdentifier('queue_id') .
            " = " . $this->dic->database()->quote($queueId ?? $this->queueId ?? 0, 'integer')
        ;

        $res = $this->dic->database()->query($sql);

        $row = $this->dic->database()->fetchAssoc($res);

        return $row['event'] ?? null;
    }


    public function getBucketIdFromQueueEntryById(?int $queueId = null) : int
    {
        $sql = "SELECT " . $this->dic->database()->quoteIdentifier('bucket_id') .
            " FROM " . $this->dic->database()->quoteIdentifier(self::$DB_TBL_NAME_EVENTS2LRS_QUEUE) .
            " WHERE " . $this->dic->database()->quoteIdentifier('queue_id') .
            " = " . $this->dic->database()->quote($queueId ?? $this->queueId ?? 0, 'integer')
        ;

        $res = $this->dic->database()->query($sql);

        $row = $this->dic->database()->fetchAssoc($res);

        return $row['bucket_id'] ?? 0;
    }


    public function getBtBucketStateById(?int $bucketId = null) : int
    {
        $sql = "SELECT " . $this->dic->database()->quoteIdentifier('state') .
            " FROM " . $this->dic->database()->quoteIdentifier('il_bt_bucket') .
            " WHERE " . $this->dic->database()->quoteIdentifier('id') .
            " = " . $this->dic->database()->quote($bucketId ?? $this->bucketId ?? 0, 'integer')
            ;

        $res = $this->dic->database()->query($sql);

        $row = $this->dic->database()->fetchAssoc($res);

        return $row['state'] ?? 99;
    }

    public function getBtBucketPercentageById(?int $bucketId = null) : int
    {
        $sql = "SELECT " . $this->dic->database()->quoteIdentifier('percentage') .
            " FROM " . $this->dic->database()->quoteIdentifier('il_bt_bucket') .
            " WHERE " . $this->dic->database()->quoteIdentifier('id') .
            " = " . $this->dic->database()->quote($bucketId ?? $this->bucketId ?? 0, 'integer')
        ;

        $res = $this->dic->database()->query($sql);

        $row = $this->dic->database()->fetchAssoc($res);

        return $row['percentage'] ?? 0;
    }

    public function loadAllQueueEntriesForCronJob() : bool
    {
        $sql = "SELECT " . $this->dic->database()->quoteIdentifier('queue_id') . ',' .
            $this->dic->database()->quoteIdentifier('state') . ',' .
            $this->dic->database()->quoteIdentifier('statement') .
            " FROM " . $this->dic->database()->quoteIdentifier(self::$DB_TBL_NAME_EVENTS2LRS_QUEUE) .
            " WHERE " . $this->dic->database()->in('state', [
                    self::$STATE_CRON_EXEC_1,
                    self::$STATE_CRON_EXEC_2,
                    self::$STATE_CRON_EXEC_3,
                ],
                false,
                'integer'
            );

        $res = $this->dic->database()->query($sql);

        while($row = $this->dic->database()->fetchAssoc($res)) {

            $this->queueEntries[$row['queue_id']] = $row;

        }

        return (bool)count($this->queueEntries);
    }

    public function loadQueueEntry(?int $queueId = null, bool $setVar = true) : ?array
    {
        $sql = "SELECT * FROM " . $this->dic->database()->quoteIdentifier(self::$DB_TBL_NAME_EVENTS2LRS_QUEUE) .
            " WHERE " . $this->dic->database()->quoteIdentifier('queue_id') .
            " = " . $this->dic->database()->quote($queueId ?? $this->queueId ?? 0, 'integer');

        $res = $this->dic->database()->query($sql);

        $entry = $this->dic->database()->fetchAssoc($res);

        if($setVar) {
            $this->refId = $entry['ref_id'] ?? null;
            $this->objId = $entry['obj_id'] ?? null;
            $this->usrId = $entry['usr_id'] ?? null;
            $this->event = $entry['event'] ?? null;
            $this->date = $entry['date'] ?? null;
            $this->state = $entry['state'] ?? null;
            $this->bucketId = $entry['bucket_id'] ?? null;
            $this->dateFailed = $entry['date_failed'] ?? null;
            $this->parameter = $entry['parameter'] ?? null;
            $this->statement = $entry['statement'] ?? null;
        }

        return $entry;
    }

    public function countQueueEntries(?int $state = null) : int
    {
        $whereState = is_null($state) ? '' :
            ' WHERE ' . $this->dic->database()->quoteIdentifier('state') .
            ' = ' . $this->dic->database()->quote($state, 'integer');

        $sql = "SELECT count(*) num FROM " .
            $this->dic->database()->quoteIdentifier(self::$DB_TBL_NAME_EVENTS2LRS_QUEUE) .
            $whereState;

        $res = $this->dic->database()->query($sql);

        $row = $row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT);;

        if(is_null($state)) {
            $this->numQueueEntries = $row->num;
        }

        return $row->num;
    }

    public function setQueueId(int $queueId)
    {
        $this->queueId = $queueId;
    }

    public function setQueueIdAndLoadEntry(int $queueId) : bool
    {
        $this->setQueueId($queueId);

        $this->loadQueueEntry();

        return true;
    }

    public function getQueueEntriesWithStateFailed(array $filterValue = [], ?int $state = null) : array
    {
        $data = [];

        $state = $state ?? self::$STATE_CRON_FAILED;

        $whereObjId = !$filterValue['obj_id'] ? '' : " AND " .
            $this->dic->database()->quoteIdentifier('obj_id') . " = " .
            $this->dic->database()->quote($filterValue['obj_id'], 'integer');

        $whereDateFailed = !$filterValue['date_failed'] ? '' : " AND " .
            $this->dic->database()->quoteIdentifier('date_failed') . " LIKE '" .
            $filterValue['date_failed'] .
            "%'";


            $sql = "SELECT * FROM " .
            $this->dic->database()->quoteIdentifier(self::$DB_TBL_NAME_EVENTS2LRS_QUEUE) .
            ' WHERE ' . $this->dic->database()->quoteIdentifier('state') .
            ' >= ' . $this->dic->database()->quote($state, 'integer') .
            $whereObjId . $whereDateFailed;

        $res = $this->dic->database()->query($sql);

        while($row = $res->fetchAssoc($res)) {

            $data[$row['queue_id']] = $row;

        }

        return $data;
    }

    public function getQueueEntriesWithStateScheduled(bool $setRunning = false, bool  $onlyStatement = false) : array
    {

        #$this->dic->logger()->root()->dump('########################################################## getQueueEntriesWithStateScheduled');
        $data =
        $queueIds = [];

        $filter = !$onlyStatement ? '*' : $this->dic->database()->quote('queue_id', 'text') . ', ' . $this->dic->database()->quote('statement', 'text');

        # " . $filter . "

        $sql = "SELECT * FROM " .
            $this->dic->database()->quoteIdentifier(self::$DB_TBL_NAME_EVENTS2LRS_QUEUE) .
            " WHERE " . $this->dic->database()->quoteIdentifier('state') .
             "=" . $this->dic->database()->quote(self::$STATE_TASK_SCHEDULE, 'integer');

        #$this->dic->logger()->root()->dump($sql);

        $res = $this->dic->database()->query($sql);

        while($row = $this->dic->database()->fetchAssoc($res)) {

            $data[$row['queue_id']] = $onlyStatement ? $row['statement'] : $row;

            $queueIds[] = $row['queue_id'];
        }

        #$this->dic->logger()->root()->dump($data);

        if($setRunning && count($queueIds) > 0) {

            $where = $this->dic->database()->in('queue_id', $queueIds,false, 'integer');

            $this->dic->database()->manipulate("UPDATE " . $this->dic->database()->quoteIdentifier(self::$DB_TBL_NAME_EVENTS2LRS_QUEUE) .
                " SET " . $this->dic->database()->quoteIdentifier('state') . " = " . $this->dic->database()->quote(self::$STATE_RUNNING, 'integer') .
                " WHERE queue_id IN (" . implode(',', $queueIds) . ")");
        }

        #$this->dic->logger()->root()->dump($data);

        return $data;

    }

    public function getQueueEntriesWithStateInitialized(bool $setRunning = false, bool  $onlyStatement = false, ?int $userId = null) : array
    {
        $data =
        $queueIds = [];

        $filter = !$onlyStatement ? '*' : $this->dic->database()->quote('queue_id', 'text') . ', ' . $this->dic->database()->quote('statement', 'text');

        # " . $filter . "

        $fieldUsrId = $this->dic->database()->quoteIdentifier('usr_id');
        $andWhere = !$userId ? '' : " AND $fieldUsrId = " . $this->dic->database()->quote($userId, 'integer');

        $sql = "SELECT * FROM " .
            $this->dic->database()->quoteIdentifier(self::$DB_TBL_NAME_EVENTS2LRS_QUEUE) .
            " WHERE " . $this->dic->database()->quoteIdentifier('state') .
            "=" . $this->dic->database()->quote(self::$STATE_INIT, 'integer') .
            $andWhere;

        $res = $this->dic->database()->query($sql);

        while($row = $this->dic->database()->fetchAssoc($res)) {

            if($row['event'] === 'handleQueueEntries') {

                $this->dic->database()->manipulate("DELETE FROM " . $this->dic->database()->quoteIdentifier(self::$DB_TBL_NAME_EVENTS2LRS_QUEUE) .
                    " WHERE " . $this->dic->database()->quoteIdentifier('queue_id') .
                    "=" . $this->dic->database()->quote($row['queue_id'], 'integer')
                );

                continue;

            }

            $data[$row['queue_id']] = $onlyStatement ? $row['statement'] : $row;

            $queueIds[] = $row['queue_id'];
        }

        if($setRunning && count($queueIds) > 0) {

            $where = $this->dic->database()->in('queue_id', $queueIds,false, 'integer');

            $this->dic->database()->manipulate("UPDATE " . $this->dic->database()->quoteIdentifier(self::$DB_TBL_NAME_EVENTS2LRS_QUEUE) .
                " SET " . $this->dic->database()->quoteIdentifier('state') . " = " . $this->dic->database()->quote(self::$STATE_RUNNING, 'integer') .
                " WHERE queue_id IN (" . implode(',', $queueIds) . ")");
        }

        return $data;

    }

}