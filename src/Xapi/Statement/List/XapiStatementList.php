<?php
namespace ILIAS\Plugin\Events2Lrs\Xapi\Statement;
/* Copyright (c) internetlehrer GmbH, Extended GPL, see LICENSE */

use Exception;
use JsonSerializable;


/**
 * Class XapiStatementList
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 */
class XapiStatementList implements JsonSerializable
{
	/**
	 * @var XapiStatement[]
	 */
	protected $statements = [];
	
	/**
	 * @param XapiStatement $statement
	 */
	public function addStatement(XapiStatement $statement)
	{
		$this->statements[] = $statement;
	}
	
	/**
	 * @return XapiStatement[]
	 */
	public function getStatements(): array
	{
		return $this->statements;
	}

    /**
     * @return string
     * @throws Exception
     */
	public function getPostBody(): string
    {
		if(DEVMODE)
		{
			return json_encode($this->jsonSerialize(), JSON_PRETTY_PRINT);
		}
		
		return json_encode($this->jsonSerialize());
	}

    /**
     * @return array
     * @throws Exception
     */
	public function jsonSerialize(): array
    {
		$jsonSerializable = [];
		
		foreach($this->statements as $statement)
		{

			$jsonSerializable[] = $statement->jsonSerialize();

		}

		return $jsonSerializable;
	}

}
