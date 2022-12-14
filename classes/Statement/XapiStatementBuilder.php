<?php
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

namespace ILIAS\Plugin\Events2Lrs\Xapi\Statement;

use Closure;
use Exception;
use ILIAS\DI\Container;
use ILIAS\Plugin\Events2Lrs\Statement as ilgStmt;

class XapiStatementBuilder
{
    /**
     * @var Container
     */
    protected $dic;

    /**
     * @var Closure
     */
    protected $logger;

    /**
     * @var $this
     */
    private static $instance;

    /**
     * @var string
     */
    protected $event;
    /**
     * @var XapiStatementList
     */
    private $statementsList;

    /**
     * @var array
     */
    private $xapiStatements = [];

    public function __construct(string $event = '')
    {
        global $DIC; /**@var Container $DIC */

        $this->dic = $DIC;

        $this->event = $event;

        $this->statementsList = new XapiStatementList();

    }

    public static function getInstance(string $event = '') : self
    {
        return $instance ?? $instance = new self($event);
    }



    /**
     * @throws Exception
     */
    public function buildPostBody(array $param) : string
    {
        $paramList = (!count($param[0] ?? [])) ? [$param] : $param;

        foreach ($paramList as $item) {

            $this->createXapiStatementAndAddToStatementList($item);

        }

        #$statementList = $this->createStatementList($param);

        #$this->dic->logger()->root()->dump($statementList);

        return $this->statementsList->getPostBody();

    }


    /**
     * @param array $param
     * @return XapiStatementBuilder
     */
    public function createXapiStatementAndAddToStatementList(array $param) : self # \ILIAS\Plugin\Events2Lrs\Xapi\Statement\XapiStatementList
    {

        $eventBasedStatementClass = $this->event ? 'ILIAS\Plugin\Events2Lrs\Statement\\' : 'ILIAS\Plugin\Events2Lrs\Xapi\Statement\\';
        $eventBasedStatementClass .= $this->event ? ucfirst($this->event) : 'XapiStatement';

        #echo $eventBasedStatementClass; exit;

        /** @var ilgStmt\AfterChangeEvent|ilgStmt\ReadCounterChange|ilgStmt\TrackIliasLearningModulePageAccess|ilgStmt\UiEvent|ilgStmt\UpdateStatus|XapiStatement $eventBasedStatementClass */
        $statement = new $eventBasedStatementClass(
            \ilEvents2LrsPlugin::getLrsType(),
            $param,
            $this->event
        );

        $this->addStatementToStatementsList($statement);

        return $this;
    }

    public function addStatementToStatementsList(XapiStatement $statement) : self
    {
        $this->statementsList->addStatement($statement);

        $this->xapiStatements[] = $statement;

        return $this;
    }

    public function getXapiStatements() : array
    {

        return $this->xapiStatements;

    }

    public function getStatementsList() : XapiStatementList
    {

        return $this->statementsList;

    }

}