<?php

namespace go\modules\tutorial\gtd\convert;

use GO;
use go\core\data\convert;
use go\modules\tutorial\gtd\model\Thought;
use go\modules\tutorial\gtd\model\ThoughtList;

class Spreadsheet extends convert\Spreadsheet {

	/**
	 * List headers to exclude
	 * @var string[]
	 */
	public static $excludeHeaders = ['recurrenceRule'];
	
	protected function init() {
		$this->addColumn('recurrenceRule', go()->t('Recurrence', 'community', 'thoughts'));
		$this->addColumn('list', go()->t('List', 'community', 'thoughts'));
	}

	protected function exportRecurrenceRule(Thought $thought) {
		return json_encode($thought->getRecurrenceRule());
	}

	protected function importRecurrenceRule(Thought $thought, $value, array $values) {
		if(!empty($value)) {
			$thought->setRecurrenceRule(json_decode($value));
		}
	}


	protected function exportList(Thought $thought) {
		$thoughtlist = ThoughtList::findById($thought->thoughtlistId, ['name']);
		return $thoughtlist->name;
	}

	protected function importList(Thought $thought, $value, array $values) {
		$thoughtlist = ThoughtList::find(['id'])->where('name', '=', $value);
		if($thoughtlist) {
			$thought->thoughtlistId = $thoughtlist->id;
		}
	}
}
