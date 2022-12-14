<?php
namespace ILIAS\Plugin\Events2Lrs\Event\Services\Tracking;
/**
 * Class H5P (former UiEvent)
 *
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author  Christian Stepper <stepper@internetlehrer-gmbh.de>
 */


use ILIAS\DI\Container;

use ILIAS\Plugin\Events2Lrs\Event\EventHandler;

use ILIAS\Plugin\Events2Lrs\Model\DbEvents2LrsQueue;


class H5P extends EventHandler
{
    use DbEvents2LrsQueue;

    public function __construct(int $queueId)
    {
        global $DIC; /** @var Container $DIC */

        $this->dic = $DIC;

        $this->event = 'H5P';

        #if($this->mergeStatement($queueId)) {

            parent::__construct($queueId);

        #}

    }

    private function mergeStatement(int $queueId) : bool
    {

        $entry = $this->loadQueueEntry($queueId, false);

        $statement = json_decode($entry['statement'], 1)[0];

        $parameter = json_decode($entry['parameter'], 1);

        $fromEvent = $parameter['uiEventData'];

        $statement['verb'] = $fromEvent['verb'];

        $this->updateQueueEntryWithStatementById(json_encode($statement), $queueId);

        return true;
    }
}