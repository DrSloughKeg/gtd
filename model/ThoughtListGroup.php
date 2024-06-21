<?php


namespace go\modules\tutorial\gtd\model;

use go\core\orm\Mapping;
use go\core\orm\Property;
use go\core\validate\ErrorCode;

class ThoughtListGroup extends Property
{
	/** @var int PK */
	public $id;

	/** @var int FK to thoughtlist this column belongs to */
	protected $thoughtlistId;

	/** @var string Column name */
	public $name;

	/** @var string 6 char hex code */
	public $color;

	protected $sortOrder;

	/** @var Progress if set the progress of a thought will change when the thought goes into this column */
	protected $progressChange;

	protected static function defineMapping(): Mapping
	{
		return parent::defineMapping()
			->addTable("thoughts_thoughtlist_group", "group");
	}

	public function getProgressChange() {
		return $this->progressChange ? Progress::$db[$this->progressChange] : null;
	}

	public function setProgressChange($value) {
		if($value == null) {
			$this->progressChange = null;
			return;
		}


		$key = array_search($value, Progress::$db, true);
		if($key === false) {
			$this->setValidationError('progress', ErrorCode::INVALID_INPUT, 'Incorrect Progress value for thought: ' . $value);
		} else
			$this->progressChange = $key;
	}
}