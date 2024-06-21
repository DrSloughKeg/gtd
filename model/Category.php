<?php
/**
 * @copyright (c) 2019, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
namespace go\modules\tutorial\gtd\model;
						
use go\core\db\Criteria;
use go\core\jmap\Entity;
use go\core\model\Acl;
use go\core\model\Module;
use go\core\orm\Filters;
use go\core\orm\Mapping;
use go\core\validate\ErrorCode;

/**
 * Category model
 */
class Category extends Entity {
	
	/** @var int */
	public $id;

	/** @var string */
	public $name;

	/** @var int could be NULL for global categories */
	public $ownerId;

	/** @var int when not null this category is only visible when the thoughtlist is selected (no ACL checking allowed)  */
	public $thoughtlistId;

	protected static function defineMapping(): Mapping
	{
		return parent::defineMapping()
			->addTable("thoughts_category", "category");
	}

	protected function init()
	{
		parent::init();

		if($this->isNew())  {
			$this->ownerId = go()->getUserId();
		}
	}

	protected function internalGetPermissionLevel(): int
	{
		//global category mayb only be created by admins
		if(empty($this->thoughtlistId) && empty($this->ownerId)) {
			return Module::findByName('community', 'thoughts')
				->getUserRights()
				->mayChangeCategories ? Acl::LEVEL_MANAGE : Acl::LEVEL_READ;
		}

		if(isset($this->thoughtlistId)) {
			$thoughtlist = ThoughtList::findById($this->thoughtlistId);

			return $thoughtlist->getPermissionLevel() >= Acl::LEVEL_MANAGE ? Acl::LEVEL_DELETE : Acl::LEVEL_READ;
		} else {
			return $this->ownerId == go()->getUserId() ? Acl::LEVEL_MANAGE : Acl::LEVEL_READ;
		}
	}

	protected function canCreate(): bool
	{
		return $this->internalGetPermissionLevel() > Acl::LEVEL_READ;
	}


	public static function getClientName(): string
	{
		return "ThoughtCategory";
	}

	protected static function textFilterColumns(): array
	{
		return ['name'];
	}

	protected static function defineFilters(): Filters
	{
		return parent::defineFilters()
			->add('ownerId', function(Criteria $criteria, $value) {
				$criteria->where('ownerId', '=', $value)
					->andWhere('thoughtlistId' , '=', null);
			})
			->add('thoughtlistId', function(Criteria $criteria, $value) {
				$criteria->where('thoughtlistId', '=', $value);
			})
			->add('global', function(Criteria $criteria, $value) {
				$op = $value ? '=' : '!=';
				$criteria->where('thoughtlistId', $op, null)
					->andWhere('ownerId', $op, null);
			});
	}

}