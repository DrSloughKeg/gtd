<?php
/**
 * @copyright (c) 2018, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace go\modules\tutorial\gtd\controller;

use go\core\exception\Forbidden;
use go\core\jmap\Entity;
use go\core\jmap\EntityController;
use go\core\model\Module as CoreModule;
use go\modules\tutorial\gtd\model;

class ThoughtList extends EntityController
{

	protected function entityClass(): string
	{
		return model\ThoughtList::class;
	}

	public function query($params)
	{
		return $this->defaultQuery($params);
	}

	public function get($params)
	{
		return $this->defaultGet($params);
	}

	public function set($params)
	{
		return $this->defaultSet($params);
	}

	protected function canDestroy(Entity $entity): bool
	{
		if(!$this->rights->mayChangeThoughtlists) {
			return false;
		}
		return parent::canDestroy($entity);
	}

	public function changes($params)
	{
		return $this->defaultChanges($params);
	}
}