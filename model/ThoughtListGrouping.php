<?php


namespace go\modules\tutorial\gtd\model;

use go\core\jmap\Entity;
use go\core\orm\Mapping;

class ThoughtListGrouping extends Entity
{
	/** @var int PK */
	public $id;

	/** @var string Column name */
	public $name;

	protected $order;

	protected static function defineMapping(): Mapping
	{
		return parent::defineMapping()
			->addTable("thoughts_thoughtlist_grouping", "g");
	}
}