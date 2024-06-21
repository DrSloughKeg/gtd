<?php
/**
 * @copyright (c) 2019, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace go\modules\tutorial\gtd\model;

use Exception;
use go\core\acl\model\AclOwnerEntity;
use go\core\db\Criteria;
use go\core\model\Acl;
use go\core\model\Module;
use go\core\orm\Filters;
use go\core\orm\Mapping;
use go\core\orm\Property;
use go\core\orm\Query;
use go\core\util\ArrayObject;
use GO\Projects2\Model\ProjectEntity;

/**
 * Thoughtlist model
 */
class ThoughtList extends AclOwnerEntity
{
	const List = 1;
	const Board = 2;
	const Project = 3;
	const Support = 4;

	const Roles = [
		self::List => 'list',
		self::Board => 'board',
		self::Project => 'project',
		self::Support => 'support'
	];

	/** @var int */
	public $id;

	/** @var string */
	public $name;

	/** @var string What kind of list: 'list', 'board' */
	protected $role = self::List;

	public function getRole() : string {
		return self::Roles[$this->role] ?? 'list';
	}

	/**
	 *
	 * @param string $value ['list'|'board'|'project']
	 */
	public function setRole(string $value) {
		$key = array_search($value, self::Roles, true);
		if($key === false) {
			$this->setValidationError('role', 10, 'Incorrect role value for thoughtlist');
		} else
			$this->role = $key;
	}

	/** @var string if a longer description then name s needed */
	public $description;

	/** @var int */
	public $createdBy;

	/** @var int */
	public $ownerId;

	/** @var int */
	public $aclId;

	/** @var int */
	public $version = 1;

	public $groups = [];

	public $projectId = null;

	public $groupingId = null;

	protected static function defineFilters(): Filters
	{
		return parent::defineFilters()
			->add('role', function (Criteria $criteria, $value) {
				$roleID = array_search($value, self::Roles, true);
				$criteria->where(['role' => $roleID]);
			})
			->add('projectId', function (Criteria $criteria, $value) {

				$criteria->where(['projectId' => $value]);
			});

	}

	protected static function textFilterColumns(): array
	{
		return ['name'];
	}

	protected static function defineMapping(): Mapping
	{
		return parent::defineMapping()
			->addTable("thoughts_thoughtlist", "thoughtlist")
			->addUserTable('thoughts_thoughtlist_user', "ut", ['id' => 'thoughtlistId'])
			->addArray('groups', ThoughtListGroup::class, ['id' => 'thoughtlistId'], ['orderBy'=>'sortOrder']);
	}

	protected function internalSave(): bool
	{
		if ($this->isNew()) {

			if($this->role == self::Board) {
				if (empty($this->groups)) {

					$this->setValue('groups', [
						['name' => go()->t('In progress', 'community', 'thoughts'), 'progressChange' => Progress::$db[Progress::InProcess]],
						['name' => go()->t('Completed', 'community', 'thoughts'), 'progressChange' => Progress::$db[Progress::Completed]]
					]);
				}
			} elseif($this->role == self::Support) {

			}

			//If this thoughtlist is for a project then take over the ACL
			if(isset($this->projectId)) {
				$project = ProjectEntity::findById($this->projectId, ['id', 'acl_id']);
				$this->aclId = $project->acl_id;
			}
		}
		return parent::internalSave();
	}


	/**
	 * Create a list for a project and return its id
	 *
	 * @param int $projectId
	 * @return ThoughtList
	 * @throws Exception
	 */
	public static function createForProject(int $projectId) :ThoughtList
	{
		$project = ProjectEntity::findById($projectId, ['id', 'name', 'acl_id']);

		$thoughtlist = new self();
		$thoughtlist->setRole('project');
		$thoughtlist->setValues([
			'name' => go()->t('Thoughtlist', 'community','thoughts') . ' ' . $project->name,
			'createdBy' => go()->getUserId(),
			'aclId' => $project->acl_id,
			'projectId' => $projectId
		]);
		$thoughtlist->save();
		return $thoughtlist;

	}

	protected function canCreate(): bool
	{
		return Module::findByName('community', 'thoughts')
			->getUserRights()->mayChangeThoughtlists;
	}

	protected static function checkAclJoinEntityTable()
	{
		return (new Query())
			->join("thoughts_thoughtlist", 'entity', 'entity.aclId = acl.id and entity.role != ' . self::Project);

	}

	public static function sort(Query $query, ArrayObject $sort): Query
	{
		if(isset($sort['group'])) {
			$query->join("thoughts_thoughtlist_grouping", "grouping", "grouping.id = thoughtlist.groupingId", "LEFT");
			$sort->renameKey("group", "grouping.name");
		}
		return parent::sort($query, $sort);
	}
}