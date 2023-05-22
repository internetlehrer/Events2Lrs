<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\Events2Lrs\Task;

use ILIAS\BackgroundTasks\Bucket;
use ILIAS\BackgroundTasks\Implementation\Bucket\State;
use ILIAS\BackgroundTasks\Implementation\Tasks\UserInteraction\UserInteractionRequiredException;
use ILIAS\BackgroundTasks\Implementation\Tasks\UserInteraction\UserInteractionSkippedException;
use ILIAS\BackgroundTasks\Task\UserInteraction;
use ILIAS\BackgroundTasks\Implementation\TaskManager\BasicTaskManager;
use ILIAS\BackgroundTasks\Implementation\TaskManager\AsyncTaskManager;
use ILIAS\DI\Container;
use ILIAS\BackgroundTasks\Implementation\TaskManager\SyncTaskManager;
use ILIAS\BackgroundTasks\Implementation\TaskManager\PersistingObserver;
use ILIAS\BackgroundTasks\Implementation\Persistence\BasicPersistence;
use ILIAS\BackgroundTasks\Implementation\Persistence\BucketContainer;

/**
 * Class TaskManager
 *
 * @package ILIAS\Plugin\Events2Lrs\Task
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */
class TaskManager extends AsyncTaskManager
#class TaskManager extends SyncTaskManager
{
    const CMD_START_WORKER = 'startBackgroundTaskWorker';

    /**
     * @var Container
     */
    protected $dic;

    use \ILIAS\Plugin\Events2Lrs\Model\DbEvents2LrsQueue;

    /**
     * This will add an Observer of the Task and start running the task.
     *
     * @param Bucket $bucket
     *
     * @return mixed|void
     * @throws \Exception
     *
     */
    public function run(Bucket $bucket)
    {
        global $DIC;

        $this->dic = $DIC;

        if ((float)ILIAS_VERSION_NUMERIC < 7.2) {
            $persistence = \ILIAS\BackgroundTasks\Implementation\Persistence\BasicPersistence::instance();
        } else {
            $persistence = \ILIAS\BackgroundTasks\Implementation\Persistence\BasicPersistence::instance($DIC->database());
        }

        $persistingObserver = new PersistingObserver($bucket, $persistence);

        $bucket->setState(State::SCHEDULED);

        $bucket->setCurrentTask($bucket->getTask());

        $this->dic->backgroundTasks()->persistence()->saveBucketAndItsTasks($bucket);

        $this->bucketId = $DIC->backgroundTasks()->persistence()->getBucketContainerId($bucket);
        $this->dic->logger()->root()->debug("[BT MAN] ########################## #[BT MAN BOF] bucketId $this->bucketId ");

        $this->dic->logger()->root()->debug("[BT MAN] ########################### [BT MAN BOF] updateQueueEntryWithStateScheduledById STATE_TASK_SCHEDULE ");
        $this->state = self::$STATE_TASK_SCHEDULE;
        $this->updateQueueEntryWithStateScheduledById();

        $this->dic->logger()->root()->debug('[BT MAN] ########################### [BT MAN BOF] updateQueueEntryWithBucketId ');
        $this->updateQueueEntryWithBucketId();

        // todo enable for sync exec if initialized by plugin RouterGUI
        #parent::run($bucket);


        // Call SOAP-Server
        $soap_client = new \ilSoapClient();
        $soap_client->setResponseTimeout(1);
        $soap_client->enableWSDL(true);
        $soap_client->init();
        $session_id = session_id();
        $client_id = $_COOKIE['ilClientId'];

        #$DIC->logger()->root()->dump([$session_id, $client_id]);

        try {
            $call = $soap_client->call(self::CMD_START_WORKER, array(
                $session_id . '::' . $client_id,
            ));
        } catch(\Exception $e) {

            $this->dic->logger()->root()->info("Soap Issue " . $e);

        }

        return true;
    }

    public static function getEventTask() : array
    {
        global $DIC; /** @var Container $DIC */

        /*
        $jsonFileContent =  $DIC->refinery()->to()->string()->transform(
            file_get_contents( dirname(__DIR__, 2) . '/plugin.ini.json')
        );
        */

        $jsonFileContent = file_get_contents( dirname(__DIR__, 2) . '/plugin.ini.json');

        $tasks = json_decode($jsonFileContent, 1);

        return $tasks['eventTask'] ?? [];
    }
}
