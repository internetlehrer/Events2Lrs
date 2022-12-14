<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitf7fa6595d845659fbdd0171473fcfdc4
{
    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Events2LrsRouterGUI' => __DIR__ . '/../..' . '/classes/Router/class.Events2LrsRouterGUI.php',
        'ILIAS\\Plugin\\Events2Lrs\\Event\\EventHandler' => __DIR__ . '/../..' . '/classes/Event/EventHandler.php',
        'ILIAS\\Plugin\\Events2Lrs\\Event\\Modules\\Test\\FinishTestPass' => __DIR__ . '/../..' . '/classes/Event/FinishTestPass.php',
        'ILIAS\\Plugin\\Events2Lrs\\Event\\Modules\\Test\\FinishTestResponse' => __DIR__ . '/../..' . '/classes/Event/FinishTestResponse.php',
        'ILIAS\\Plugin\\Events2Lrs\\Event\\Modules\\Test\\ResumeTestPass' => __DIR__ . '/../..' . '/classes/Event/ResumeTestPass.php',
        'ILIAS\\Plugin\\Events2Lrs\\Event\\Modules\\Test\\StartTestPass' => __DIR__ . '/../..' . '/classes/Event/StartTestPass.php',
        'ILIAS\\Plugin\\Events2Lrs\\Event\\Modules\\Test\\SuspendTestPass' => __DIR__ . '/../..' . '/classes/Event/SuspendTestPass.php',
        'ILIAS\\Plugin\\Events2Lrs\\Event\\Services\\Tracking\\AfterChangeEvent' => __DIR__ . '/../..' . '/classes/Event/AfterChangeEvent.php',
        'ILIAS\\Plugin\\Events2Lrs\\Event\\Services\\Tracking\\DeleteAllBtEntriesByBucketId' => __DIR__ . '/../..' . '/classes/Event/DeleteAllBtEntriesByBucketId.php',
        'ILIAS\\Plugin\\Events2Lrs\\Event\\Services\\Tracking\\H5P' => __DIR__ . '/../..' . '/classes/Event/H5P.php',
        'ILIAS\\Plugin\\Events2Lrs\\Event\\Services\\Tracking\\HandleQueueEntries' => __DIR__ . '/../..' . '/classes/Event/HandleQueueEntries.php',
        'ILIAS\\Plugin\\Events2Lrs\\Event\\Services\\Tracking\\ReadCounterChange' => __DIR__ . '/../..' . '/classes/Event/ReadCounterChange.php',
        'ILIAS\\Plugin\\Events2Lrs\\Event\\Services\\Tracking\\SendAllStatements' => __DIR__ . '/../..' . '/classes/Event/SendAllStatements.php',
        'ILIAS\\Plugin\\Events2Lrs\\Event\\Services\\Tracking\\SendStatementsByQueueId' => __DIR__ . '/../..' . '/classes/Event/SendStatementsByQueueId.php',
        'ILIAS\\Plugin\\Events2Lrs\\Event\\Services\\Tracking\\TrackIliasLearningModulePageAccess' => __DIR__ . '/../..' . '/classes/Event/TrackIliasLearningModulePageAccess.php',
        'ILIAS\\Plugin\\Events2Lrs\\Event\\Services\\Tracking\\UpdateStatus' => __DIR__ . '/../..' . '/classes/Event/UpdateStatus.php',
        'ILIAS\\Plugin\\Events2Lrs\\Model\\DbEvents2LrsQueue' => __DIR__ . '/../..' . '/classes/Model/DbEvents2LrsQueue.php',
        'ILIAS\\Plugin\\Events2Lrs\\Statement\\AbstractStatement' => __DIR__ . '/../..' . '/classes/Statement/AbstractStatement.php',
        'ILIAS\\Plugin\\Events2Lrs\\Statement\\AfterChangeEvent' => __DIR__ . '/../..' . '/classes/Statement/AfterChangeEvent.php',
        'ILIAS\\Plugin\\Events2Lrs\\Statement\\FinishTestPass' => __DIR__ . '/../..' . '/classes/Statement/FinishTestPass.php',
        'ILIAS\\Plugin\\Events2Lrs\\Statement\\FinishTestResponse' => __DIR__ . '/../..' . '/classes/Statement/FinishTestResponse.php',
        'ILIAS\\Plugin\\Events2Lrs\\Statement\\H5P' => __DIR__ . '/../..' . '/classes/Statement/H5P.php',
        'ILIAS\\Plugin\\Events2Lrs\\Statement\\ReadCounterChange' => __DIR__ . '/../..' . '/classes/Statement/ReadCounterChange.php',
        'ILIAS\\Plugin\\Events2Lrs\\Statement\\ResumeTestPass' => __DIR__ . '/../..' . '/classes/Statement/ResumeTestPass.php',
        'ILIAS\\Plugin\\Events2Lrs\\Statement\\StartTestPass' => __DIR__ . '/../..' . '/classes/Statement/StartTestPass.php',
        'ILIAS\\Plugin\\Events2Lrs\\Statement\\SuspendTestPass' => __DIR__ . '/../..' . '/classes/Statement/SuspendTestPass.php',
        'ILIAS\\Plugin\\Events2Lrs\\Statement\\TestPass' => __DIR__ . '/../..' . '/classes/Statement/TestPass.php',
        'ILIAS\\Plugin\\Events2Lrs\\Statement\\TrackIliasLearningModulePageAccess' => __DIR__ . '/../..' . '/classes/Statement/TrackIliasLearningModulePageAccess.php',
        'ILIAS\\Plugin\\Events2Lrs\\Statement\\UpdateStatus' => __DIR__ . '/../..' . '/classes/Statement/UpdateStatus.php',
        'ILIAS\\Plugin\\Events2Lrs\\Task\\DbInsertEventData' => __DIR__ . '/../..' . '/classes/Task/DbInsertEventData.php',
        'ILIAS\\Plugin\\Events2Lrs\\Task\\DeleteAllBtEntriesByBucketId' => __DIR__ . '/../..' . '/classes/Task/DeleteAllBtEntriesByBucketId.php',
        'ILIAS\\Plugin\\Events2Lrs\\Task\\HandleQueueEntries' => __DIR__ . '/../..' . '/classes/Task/HandleQueueEntries.php',
        'ILIAS\\Plugin\\Events2Lrs\\Task\\SendAllStatements' => __DIR__ . '/../..' . '/classes/Task/SendAllStatements.php',
        'ILIAS\\Plugin\\Events2Lrs\\Task\\SendAllStatementsByStateScheduled' => __DIR__ . '/../..' . '/classes/Task/SendAllStatementsByStateScheduled.php',
        'ILIAS\\Plugin\\Events2Lrs\\Task\\SendMultiStatement' => __DIR__ . '/../..' . '/classes/Task/SendMultiStatement.php',
        'ILIAS\\Plugin\\Events2Lrs\\Task\\SendSingleStatement' => __DIR__ . '/../..' . '/classes/Task/SendSingleStatement.php',
        'ILIAS\\Plugin\\Events2Lrs\\Task\\SendSingleStatementFinishTestPassRaiseSendMultiStatement' => __DIR__ . '/../..' . '/classes/Task/SendSingleStatementFinishTestPassRaiseSendMultiStatement.php',
        'ILIAS\\Plugin\\Events2Lrs\\Task\\SendStatementsByQueueId' => __DIR__ . '/../..' . '/classes/Task/SendStatementsByQueueId.php',
        'ILIAS\\Plugin\\Events2Lrs\\Task\\TaskManager' => __DIR__ . '/../..' . '/classes/Task/TaskManager.php',
        'ILIAS\\Plugin\\Events2Lrs\\Xapi\\Request\\XapiRequest' => __DIR__ . '/../..' . '/src/Xapi/Request/XapiRequest.php',
        'ILIAS\\Plugin\\Events2Lrs\\Xapi\\Statement\\XapiStatement' => __DIR__ . '/../..' . '/src/Xapi/Statement/XapiStatement.php',
        'ILIAS\\Plugin\\Events2Lrs\\Xapi\\Statement\\XapiStatementBuilder' => __DIR__ . '/../..' . '/classes/Statement/XapiStatementBuilder.php',
        'ILIAS\\Plugin\\Events2Lrs\\Xapi\\Statement\\XapiStatementInterface' => __DIR__ . '/../..' . '/src/Xapi/Statement/XapiStatementInterface.php',
        'ILIAS\\Plugin\\Events2Lrs\\Xapi\\Statement\\XapiStatementList' => __DIR__ . '/../..' . '/src/Xapi/Statement/List/XapiStatementList.php',
        'ILIAS\\Plugins\\Events2Lrs\\DI\\CmiXapi\\Extension' => __DIR__ . '/../..' . '/src/DI/CmiXapi/Extension.php',
        'ILIAS\\Plugins\\Events2Lrs\\DI\\CmiXapi\\ExtensionInterface' => __DIR__ . '/../..' . '/src/DI/CmiXapi/ExtensionInterface.php',
        'ILIAS\\Plugins\\Events2Lrs\\DI\\CmiXapi\\Request' => __DIR__ . '/../..' . '/src/DI/CmiXapi/Request.php',
        'ILIAS\\Plugins\\Events2Lrs\\DI\\CmiXapi\\Request\\Forward' => __DIR__ . '/../..' . '/src/DI/CmiXapi/Forward .php',
        'ILIAS\\Plugins\\Events2Lrs\\DI\\CmiXapi\\Statement' => __DIR__ . '/../..' . '/src/DI/CmiXapi/Statement.php',
        'ILIAS\\Plugins\\Events2Lrs\\DI\\Container' => __DIR__ . '/../..' . '/src/DI/Container.php',
        'ILIAS\\Plugins\\Events2Lrs\\DI\\Database\\CompositeExpression' => __DIR__ . '/../..' . '/src/DI/Database/QueryBuilder.php',
        'ILIAS\\Plugins\\Events2Lrs\\DI\\Database\\Delete' => __DIR__ . '/../..' . '/src/DI/Database/Delete.php',
        'ILIAS\\Plugins\\Events2Lrs\\DI\\Database\\Extension' => __DIR__ . '/../..' . '/src/DI/Database/Extension.php',
        'ILIAS\\Plugins\\Events2Lrs\\DI\\Database\\ExtensionInterface' => __DIR__ . '/../..' . '/src/DI/Database/ExtensionInterface.php',
        'ILIAS\\Plugins\\Events2Lrs\\DI\\Database\\QueryBuilder' => __DIR__ . '/../..' . '/src/DI/Database/QueryBuilder.php',
        'ILIAS\\Plugins\\Events2Lrs\\DI\\Database\\Select' => __DIR__ . '/../..' . '/src/DI/Database/Select.php',
        'ILIAS\\Plugins\\Events2Lrs\\Router\\HandlePsr7XapiRequest' => __DIR__ . '/../..' . '/classes/Router/HandlePsr7XapiRequest.php',
        'ilEvents2LrsConfigGUI' => __DIR__ . '/../..' . '/classes/class.ilEvents2LrsConfigGUI.php',
        'ilEvents2LrsCron' => __DIR__ . '/../..' . '/classes/class.ilEvents2LrsCron.php',
        'ilEvents2LrsPlugin' => __DIR__ . '/../..' . '/classes/class.ilEvents2LrsPlugin.php',
        'ilEvents2LrsTableGUI' => __DIR__ . '/../..' . '/classes/class.ilEvents2LrsTableGUI.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInitf7fa6595d845659fbdd0171473fcfdc4::$classMap;

        }, null, ClassLoader::class);
    }
}