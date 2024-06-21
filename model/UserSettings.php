<?php

namespace go\modules\tutorial\gtd\model;

use GO\Calendar\Model\Calendar;
use go\core\model\User;
use go\core\orm\exception\SaveException;
use go\core\orm\Mapping;
use go\core\orm\Property;
use go\core\model;
use go\core\util\JSON;

class UserSettings extends Property {

	/**
	 * Primary key to User id
	 * 
	 * @var int
	 */
	public $userId;
	
	/**
	 * Default Note book ID
	 * 
	 * @var int
	 */
	protected $defaultThoughtlistId;

	/**
	 * @var bool
	 */
	public $rememberLastItems = true;

	/** @var string */
	protected $lastThoughtlistIds;

	/**
	 * Set due and start to the current time for new thoughts
	 *
	 * @var bool
	 */
	public $defaultDate = false;

	/**
	 * @return Mapping
	 * @throws \ReflectionException
	 */

	protected static function defineMapping(): Mapping
	{
		return parent::defineMapping()->addTable("thoughts_user_settings", "tus");
	}

	public function getDefaultThoughtlistId() {
		if(isset($this->defaultThoughtlistId)) {
			return $this->defaultThoughtlistId;
		}

		if(!model\Module::isAvailableFor('community', 'thoughts', $this->userId)) {
			return null;
		}

		$thoughtlist = ThoughtList::find()->where('createdBy', '=', $this->userId)->single();
		if(!$thoughtlist) {
			$user = User::findById($this->userId, ['displayName', 'enabled']);
			if(!$user || !$user->enabled) {
				return null;
			}

			$thoughtlist = new ThoughtList();
			$thoughtlist->createdBy = $this->userId;
			$thoughtlist->name = $user->displayName;
			if(!$thoughtlist->save()) {
				throw new SaveException($thoughtlist);
			}
		}

		if($thoughtlist) {
			$this->defaultThoughtlistId = $thoughtlist->id;

			//when coming here the models might be read only so we use this query
			$stmt = go()->getDbConnection()->update("thoughts_user_settings", ['defaultThoughtlistId' => $this->defaultThoughtlistId], ['userId' => $this->userId]);
			$stmt->execute();
			if(!$stmt->rowCount()) {
				$stmt = go()->getDbConnection()->insertIgnore("thoughts_user_settings", ['defaultThoughtlistId' => $this->defaultThoughtlistId, 'userId' => $this->userId]);
				$stmt->execute();
			}
		}

		return $this->defaultThoughtlistId;
		
	}

	public function setDefaultThoughtlistId($id) {
		$this->defaultThoughtlistId = $id;
	}



	/**
	 * @return array
	 */
	public function getLastThoughtlistIds(): array
	{
		if (!empty($this->lastThoughtlistIds)) {
			return JSON::decode($this->lastThoughtlistIds);
		}
		return [$this->getDefaultThoughtlistId()]; // The default notebook id makes sense in this case
	}

	/**
	 * @param array|null $ids
	 */
	public function setLastThoughtlistIds(?array $ids = null)
	{
		if (is_array($ids)) {
			$this->lastThoughtlistIds = JSON::encode($ids);
		} else {
			$this->lastThoughtlistIds = '';
		}
	}


}
