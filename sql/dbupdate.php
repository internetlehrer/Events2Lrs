<#1>
<?php
$tblEv2Lrs = 'ev2lrs_queue';
if(!$ilDB->tableExists($tblEv2Lrs))
{
    $fields = array (
        "queue_id" => array (
            "notnull" => true,
            "length" => 8,
            "default" => "0",
            "type" => "integer"
        )
        ,"ref_id" => array (
            "notnull" => false,
            "length" => 4,
            "default" => null,
            "type" => "integer"
        )
        ,"obj_id" => array (
            "notnull" => true,
            "length" => 4,
            "default" => "0",
            "type" => "integer"
        )
        ,"usr_id" => array (
            "notnull" => true,
            "length" => 4,
            "default" => "0",
            "type" => "integer"
        )
        ,"event" => array(
            "notnull" => false,
            "type" => "text",
            "length" => 64
        )
        ,"date" => array (
            "notnull" => false,
            "type" => "timestamp"
        )
        ,"state" => array (
            "notnull" => true,
            "length" => 2,
            "default" => "2",
            "type" => "integer"
        )
        ,"bucket_id" => array (
            "notnull" => false,
            "length" => 8,
            "default" => null,
            "type" => "integer"
        )
        ,"date_failed" => array (
            "notnull" => false,
            "type" => "timestamp"
        )
        ,"parameter" => array (
            "notnull" => false,
            "type" => 'clob'
        )
        ,"statement" => array (
            "notnull" => false,
            "type" => 'clob'
        )
    );
    $ilDB->createTable($tblEv2Lrs, $fields);
    $ilDB->addPrimaryKey($tblEv2Lrs, array("queue_id"));
    $ilDB->createSequence($tblEv2Lrs);
}
?>

