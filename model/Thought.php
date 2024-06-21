<?php /** @noinspection PhpUnused */

/**
 * @copyright (c) 2019, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace go\modules\tutorial\gtd\model;

use DateTimeInterface;
use Exception;
use go\core\acl\model\AclItemEntity;
use go\core\db\Criteria;
use go\core\db\Expression;
use go\core\model\Acl;
use go\core\model\Alert as CoreAlert;
use go\core\model\User;
use go\core\model\Module;
use go\core\model\UserDisplay;
use go\core\orm\CustomFieldsTrait;
use go\core\orm\exception\SaveException;
use go\core\orm\Filters;
use go\core\orm\Mapping;
use go\core\orm\Property;
use go\core\orm\Query;
use go\core\orm\SearchableTrait;
use go\core\util\{ArrayObject, DateTime, Recurrence, StringUtil, Time, UUID};
use go\core\validate\ErrorCode;
use go\modules\community\comments\model\Comment;
use go\modules\tutorial\gtd\convert\Spreadsheet;
use go\modules\tutorial\gtd\convert\VCalendar;
use go\core\util\JSON;
use JsonException;
use PDO;

/**
 * Thought model
 */
class Thought extends AclItemEntity {

	const PRIORITY_LOW = 9;
	const PRIORITY_HIGH = 1;
	const PRIORITY_NORMAL = 0;


	use SearchableTrait;
	use CustomFieldsTrait;
	
	/** @var int PK in the database */
	public $id;

	/** @var string global unique id for invites and sync  */
	protected $uid = '';

//	protected $userId;

	/** @var int The list this Thought belongs to */
	public $thoughtlistId;

	/** @var int id of user responsible for completing this thoughts  */
	public $responsibleUserId;

	/** @var int used for the kanban groups */
	public $groupId;

	/** @var int */
	public $projectId ;

	/** @var int */
	public $createdBy;

	/** @var DateTime */
	public $createdAt;

	/** @var DateTime */
	public $modifiedAt;

	/** @var int */
	public $modifiedBy;

	/** @var int */
	public $filesFolderId;

	/** @var DateTime due date (when this should be finished) */
	public $due;

	/** @var DateTime local date when this thought will be started */
	public $start;

	/** @var int Duration Estimated duration in seconds the thought takes to complete. */
	public $estimatedDuration;

	/** @var int Progress Defines the progress of this thought */
	protected $progress = Progress::NeedsAction;

	/** @var DateTime When the "progress" of either the thought or a specific participant was last updated. */
	public $progressUpdated;

	/** @var string */
	public $title;

	/** @var string */
	public $description;

	/** @var string */
	public $location;

	//public $keywords; // only in jmap

	/** @var int[] */
	public $categories;

	public $color;

	/**
	 * Start time in H:m
	 *
	 * @var string
	 */
	public $startTime;

	/**
	 * @var float
	 */
	public $latitude;

	/**
	 * @var float
	 */
	public $longitude;

	/**
	 * @var string
	 * @todo this is an override for Humble. This is probably not the right spot for this. Discuss interanlly
	 */
	protected $displayName;
	/**
	 * @var string
	 * @todo this is an override for Humble. This is probably not the right spot for this. Discuss internally.
	 */
	protected $projectName;

	//The scheduling status
	//public $status = 'confirmed';

	/**
     * If present, this object represents one occurrence of a
     * recurring object.  If present the "recurrenceRule" and
     * "recurrenceOverrides" properties MUST NOT be present.
     *
     * The value is a date-time either produced by the "recurrenceRules" of
     * the master event, or added as a key to the "recurrenceOverrides"
     * property of the master event.
     * @var DateTime
     */
	//public $recurrenceId;

  /** @var string */
  protected $recurrenceRule;

  /** @var DateTime[PatchObject] map of recurrenceId => Thought */
  //protected $recurrenceOverrides;

  /** @var boolean only set in recurrenceOverrides */
  //protected $excluded;

	/** @var int [0-9] 1 = highest priority, 9 = lowest, 0 = undefined */
	public $priority = self::PRIORITY_NORMAL;

	/** @var string free or busy */
	public $freeBusyStatus = 'free';

	/** @var string public , private, secret */
	public $privacy = 'public';

   public $replyTo;
   public $participants;

	/** @var int between 0 and 100 */
	public $percentComplete = 0;

	protected $uri;

	/** @var bool If true, use the user's default alerts and ignore the value of the "alerts" property. */
	public $useDefaultAlerts = false;

    /** @var Alert[] List of notification alerts when $useDefaultAlerts is not set */
	public $alerts = [];

	/** @var int */
	public $vcalendarBlobId;

	/**
	 * Time booked in seconds
	 *
	 * @var int
	 */
	protected $timeBooked;

	/**
	 * @var ThoughtListGroup[]
	 */
	public $group = [];


	protected static function aclEntityClass(): string
	{
		return ThoughtList::class;
	}

	protected static function aclEntityKeys(): array
	{
		return ['thoughtlistId' => 'id'];
	}

	protected static function internalRequiredProperties() : array
	{
		//Needed for support module permissions
		return array_merge(parent::internalRequiredProperties(), ['createdBy']);
	}

	protected static function defineMapping() :Mapping {
		$mapping = parent::defineMapping()
			->addTable("thoughts_thought", "thought")
			->addUserTable("thoughts_thought_user", "ut", ['id' => 'thoughtId'])
			->addMap('alerts', Alert::class, ['id' => 'thoughtId'])
			->addMap('group', ThoughtListGroup::class, ['groupId' => 'id'])
			->addScalar('categories', 'thoughts_thought_category', ['id' => 'thoughtId']);

		return static::mapRole($mapping);
	}

	protected static function mapRole(Mapping $mapping) : Mapping {
		$mapping->addQuery((new Query)
			->join('thoughts_thoughtlist', 'tl', 'tl.id=thought.thoughtListId AND tl.role != '.ThoughtList::Support)
		);

		return $mapping;
	}

	public static function converters(): array
	{
		return array_merge(parent::converters(), [VCalendar::class, Spreadsheet::class]);
	}

	protected static function internalFind(array $fetchProperties = [], bool $readOnly = false, Property $owner = null)
	{
		return parent::internalFind($fetchProperties, $readOnly, $owner); // TODO: Change the autogenerated stub
	}

	/**
	 * @throws JsonException
	 */
	public function getRecurrenceRule(): ?array {
		return empty($this->recurrenceRule) ? null : JSON::decode($this->recurrenceRule, true);
	}

	public function setRecurrenceRule($rrule) {
		if($rrule !== null) {
			$rrule = JSON::encode($rrule);
		}
		$this->recurrenceRule = $rrule;
	}

	public function getProgress(): string
	{
		return Progress::$db[$this->progress] ?? Progress::$db[1];
	}

	public function getTimeBooked(): ?int
	{
		return $this->timeBooked;
	}

	/**
	 * Set progress status
	 *
	 * @param string $value "needs-action" {@see Progress::$db}
	 * @return void
	 */
	public function setProgress(string $value) {
		$key = array_search($value, Progress::$db, true);
		if($key === false) {
			$this->setValidationError('progress', ErrorCode::INVALID_INPUT, 'Incorrect Progress value for thought: ' . $value);
		} else
			$this->progress = $key;
	}

	public function setRecurrenceRuleEncoded($rrule) {
		$this->recurrenceRule = $rrule;
	}

	protected static function textFilterColumns(): array
	{
		return ['title', 'description'];
	}

	protected function getSearchKeywords(): ?array
	{
		$keywords = [$this->title, $this->description];
		if($this->responsibleUserId) {
			$rUser = UserDisplay::findById($this->responsibleUserId);
			$keywords[] = $rUser->displayName;
		}
		if($this->thoughtlistId) {
			$thoughtlist = ThoughtList::findById($this->thoughtlistId);
			$keywords[] = $thoughtlist->name;
		}

		if($this->createdBy) {
			$creator = UserDisplay::findById($this->createdBy);
			if($creator) {
				$keywords[] = $creator->displayName;
				$keywords[] = $creator->email;
			}
		}
		return $keywords;
	}


	protected function getSearchDescription(): string
	{
		$thoughtlist = ThoughtList::findById($this->thoughtlistId);
		$desc = $thoughtlist->name;
		if(!empty($this->responsibleUserId) && ($user = User::findById($this->responsibleUserId, ['displayName']))) {
			$desc .= ' - '.$user->displayName;
		} else{
			$desc .= ' - ' . go()->t("Unassigned", "community", "thoughts");
		}

		if(!empty($this->description)) {
			$desc .= ": " . $this->description;
		}

		return $desc;
	}

	protected static function defineFilters(): Filters
	{

		return parent::defineFilters()
			->addText("title", function(Criteria $criteria, $comparator, $value, Query $query, array $filters){
				$criteria->where('title', $comparator, $value);
			})
			->add('thoughtlistId', function(Criteria $criteria, $value) {
				if(!empty($value)) {
					$criteria->where(['thoughtlistId' => $value]);
				}
			}, [])
			->add('projectId', function(Criteria $criteria, $value, Query $query) {
				if(!empty($value)) {
					if(!$query->isJoined("thoughts_thoughtlist", "thoughtlist") ){
						$query->join("thoughts_thoughtlist", "thoughtlist", "thought.thoughtlistId = thoughtlist.id");
					}
					$criteria->where(['thoughtlist.projectId' => $value]);
				}
			})
			->add('role', function(Criteria $criteria, $value, Query $query) {
				if(!$query->isJoined("thoughts_thoughtlist", "thoughtlist") ){
					$query->join("thoughts_thoughtlist", "thoughtlist", "thought.thoughtlistId = thoughtlist.id");
				}

				$roleID = array_search($value, ThoughtList::Roles, true);

				$criteria->where(['thoughtlist.role' => $roleID]);
			})
			->add('categories', function(Criteria $criteria, $value, Query $query) {
				if(!empty($value)) {
					if(!$query->isJoined("thoughts_thought_category","categories")) {
						$query->join("thoughts_thought_category", "categories", "thought.id = categories.thoughtId");
					}
					$criteria->where(['categories.categoryId' => $value]);
				}
			})->addDateTime("start", function(Criteria $criteria, $comparator, $value) {
				$criteria->where('start',$comparator,$value);
			})->addDateTime("due", function(Criteria $criteria, $comparator, $value) {
				$criteria->where('due', $comparator, $value);
			})->addNumber('percentComplete', function(Criteria $criteria, $comparator, $value) {
				$criteria->where('percentComplete', $comparator, $value);
			})->add('complete', function(Criteria $criteria, $value) {
				$criteria->where('progress', $value ? '=' : '!=', Progress::Completed);
			})->add('scheduled', function(Criteria $criteria, $value) {
				$criteria->where('start', $value ? 'IS NOT' : 'IS',null);
			})->add('responsibleUserId', function(Criteria $criteria, $value){
				//if(!empty($value)) {
					$criteria->where('responsibleUserId', '=',$value);
				//}//
			})
			->add('progress', function(Criteria $criteria, $value){
				if(!empty($value)) {
					if(!is_array($value)) {
						$value = [$value];
					}
					$value = array_map(function($el) {
						return array_search($el, Progress::$db, true);
					}, $value);
					$criteria->where('progress', '=',$value);
				}
			});

	}

	protected function internalValidate()
	{
		if(isset($this->recurrenceRule)) {
			if(empty($this->start)) {
				$this->setValidationError('start', ErrorCode::REQUIRED, 'start is required when recurrence rule is set');
			}
		}

		if(isset($this->projectId) && $this->hasConflicts()) {
			$this->setValidationError('start', ErrorCode::CONFLICT, 'this thought is in conflict with other thoughts');
		}

		parent::internalValidate();
	}

	protected function internalSave(): bool
	{
		if ($this->isNew() && empty($this->uid)) {
			$this->uid = UUID::v4();
		}

		if ($this->progress == Progress::Completed) {
			$this->percentComplete = 100;
		}
		if ($this->isModified('percentComplete')) {
			if ($this->percentComplete == 100) {
				$this->progress = Progress::Completed;

				// Remove alert for creator of this comment. Other users will get a replaced alert below.
				CoreAlert::deleteByEntity($this);


			} else if ($this->percentComplete > 0 && $this->progress == Progress::NeedsAction) {
				$this->progress = Progress::InProcess;
			}
		}

		if ($this->isModified('progress')) {
			$this->progressUpdated = new DateTime();
		}

		if (!empty($this->recurrenceRule) && $this->progress == Progress::Completed) {
			$next = $this->getNextRecurrence($this->getRecurrenceRule());
			if ($next) {
				$this->createNewThought($next);
			} else {
				$this->recurrenceRule = null;
			}
		}

		if($this->isModified('responsibleUserId') && !$this->isModified('progress')) {
			// when assigned to someone else it's progress should be needs action
			$this->progress = Progress::NeedsAction;
		}

		if(!parent::internalSave()) {
			return false;
		}

		$this->createSystemAlerts();

		// if alert can be based on start / due of thought check those properties as well
		$modified = $this->getModified('alerts');
		if (!empty($modified)) {
			$this->updateAlerts($modified['alerts']);
		}

		return true;
	}

	private function createSystemAlerts() {
		if(!CoreAlert::$enabled) {
			return;
		}
		if($this->isModified('responsibleUserId')) {

			if (isset($this->responsibleUserId)) {
				if($this->responsibleUserId != go()->getUserId()) {
					$alert = $this->createAlert(new \DateTime(), 'assigned', $this->responsibleUserId)
						->setData([
							'type' => 'assigned',
							'assignedBy' => go()->getAuthState()->getUserId()
						]);

					if (!$alert->save()) {
						throw new SaveException($alert);
					}
				}
			} else if(!$this->isNew()){
				$this->deleteAlert('assigned', $this->getOldValue('responsibleUserId'));
			}
		}

		if($this->isNew()) {

			// create an alert if someone else created a thought in your default list
			$thoughtlist = ThoughtList::findById($this->thoughtlistId, ['id', 'createdBy', 'role']);
			if($thoughtlist->getRole() == "list" && $this->createdBy != $thoughtlist->createdBy) {

				$defaultListId = User::findById($thoughtlist->createdBy, ['thoughtsSettings'])->thoughtsSettings->getDefaultThoughtlistId();
				if($defaultListId == $this->thoughtlistId) {
					$alert = $this->createAlert(new \DateTime(), 'createdforyou', $thoughtlist->createdBy)
						->setData([
							'type' => 'assigned',
							'createdBy' => $this->createdBy
						]);

					if (!$alert->save()) {
						throw new SaveException($alert);
					}
				}
			}
		} else if($this->modifiedBy != $this->createdBy){
			$this->deleteAlert('createdforyou', $this->modifiedBy);
		}
	}


	/**
	 * @throws Exception
	 */
	private function updateAlerts($modified) {

		if(!CoreAlert::$enabled)
		{
			return;
		}

		if(isset($modified[1])) {
			foreach ($modified[1] as $model) {
				if (!isset($modified[0]) || !in_array($model, $modified[0])) {
					$this->deleteAlert($model->id);
				}
			}
		}

		if(!isset($this->alerts)){
			return;
		}
		foreach($this->alerts as $alert) {
			$coreAlert = $this->createAlert($alert->at($this), $alert->id);
			if(!$coreAlert->save()) {
				throw new Exception(var_export($coreAlert->getValidationErrors(),true));
			}
		}
	}

	/**
	 * @inheritDoc
	 *
	 * @throws Exception
	 */
	public static function dismissAlerts(array $alerts)
	{

		// When a thought is updated with deleted alerts it will circle back here
		// in that case the update won't affect any rows and the changes will be empty.

		$alertIds = [];

		//The ID of the core_alert is the tag of the thought_alert.
		//But in some cases it contains a string like 'assigned' for when a thought is assinged.
		foreach($alerts as $alert) {
			if(is_numeric($alert->tag)) {
				$alertIds[] = $alert->tag;
			}
		}

		if(empty($alertIds)) {
			return;
		}

		go()->getDbConnection()->update(
			'thoughts_alert', ['acknowledged' => new DateTime()],
			(new Query)->where('id' , 'in', $alertIds)
		)->execute();

		$changes = Thought::find()
			->select("thought.id, atl.aclId, '0'")
			->fetchMode(PDO::FETCH_ASSOC)
			->join("thoughts_thoughtlist", "atl", "atl.id = thought.thoughtListId")
			->join("thoughts_alert", "a", "a.thoughtId = thought.id")
			->where('a.id' , 'in', $alertIds)
			->groupBy(['thought.id'])
			->all();

		if(!empty($changes)) {
			Thought::entityType()->changes($changes);
		}
	}

	/**
	 * @throws Exception
	 */
	protected function createNewThought(DateTimeInterface $next) {

		$values = $this->toArray();
//		unset($values['id']);
//		unset($values['progress']);
//		unset($values['responsibleUserId']);
//		unset($values['percentComplete']);
//		unset($values['progressUpdated']);
//		unset($values['freeBusyStatus']);
//		$nextThought = new Thought();
//		$nextThought->setValues($values);

		$nextThought = $this->copy();
		$nextThought->progress = Progress::NeedsAction;
		$nextThought->responsibleUserId = null;
		$nextThought->percentComplete = 0;
		$nextThought->progressUpdated = null;
		$nextThought->freeBusyStatus = 'free';

		$rrule = $this->getRecurrenceRule();
			
		if(!empty($rrule->count)) {
			$rrule->count--;
			$nextThought->setRecurrenceRule($rrule->count > 0 ? $rrule : null);
		} else if(!empty($rrule->until)) {
			$nextThought->setRecurrenceRule($rrule->until > $next ? $rrule : null);
		} else{
			$nextThought->setRecurrenceRule($rrule);
		}

		$this->recurrenceRule = null;

		$nextThought->start = $next;
		if(!empty($nextThought->due)) {
			$diff = $this->start->diff($next);
			$nextThought->due->add($diff);
		}
		if(!$nextThought->save()) {
			throw new Exception("Could not save next thought: ". var_export($nextThought->getValidationErrors(), true));
		}
	}

	/**
	 * @param array $rrule
	 * @return ?DateTimeInterface
	 */
	protected function getNextRecurrence(array $rrule): ?DateTimeInterface
	{
		$rule = Recurrence::fromArray($rrule, $this->start);
		$rule->next();
		return $rule->current();
	}

	public static function sort(Query $query, ArrayObject $sort): Query
	{
		if(isset($sort['groupOrder'])) {
			$query->join('thoughts_thoughtlist_group', 'listGroup', 'listGroup.id = thought.groupId', 'LEFT');
			$sort->renameKey('groupOrder', 'listGroup.sortOrder');
			$sort['id'] = "ASC";
		}

		if(isset($sort['thoughtlist'])) {

			if(!$query->isJoined("thoughts_thoughtlist", "thoughtlist")) {
				$query->join("thoughts_thoughtlist", "thoughtlist", "thoughtlist.id = thought.thoughtlistId");
			}
			$sort->renameKey('thoughtlist','thoughtlist.name');
		}

		//sort null dates first
		if(isset($sort['due'])) {

			$null = strtoupper($sort['due']) == 'ASC' ? 'IS NULL' : 'IS NOT NULL';

			$i = array_search('due', $sort->keys());
			$sort->insert($i, new Expression($query->getTableAlias() . '.`due` ' . $null));

		}

		if(isset($sort['start'])) {

			$null = strtoupper($sort['start']) == 'ASC' ? 'IS NULL' : 'IS NOT NULL';

			$i = array_search('start', $sort->keys());
			$sort->insert($i, new Expression($query->getTableAlias() . '.`start` ' . $null));
			$sort->insert($i + 2, $sort['start'], 'id');
		}

		if(isset($sort['responsible'])) {
			$query->join('core_user', 'responsible', 'responsible.id = '.$query->getTableAlias() . '.responsibleUserId');
			$sort->renameKey('responsible', 'responsible.displayName');
		}

		if(isset($sort['categories'])) {
			$query->join('thoughts_thought_category', 'tc', 'tc.thoughtId = '.$query->getTableAlias() . '.id', 'LEFT');
			$query->join('thoughts_category', 'categorySort', 'tc.categoryId = categorySort.id', 'LEFT');
			$sort->renameKey('categories', 'categorySort.name');
		}

		return parent::sort($query, $sort);
	}

	public function etag(): string
	{
		return '"' .$this->vcalendarBlobId . '"';
	}

	public function getUid(): string
	{
		return $this->uid;		
	}

	public function setUid(string $uid) {
		$this->uid = $uid;
	}

	public function hasUid(): bool
	{
		return !empty($this->uid);
	}

	public function getUri(): string
	{
		if(!isset($this->uri)) {
			$this->uri = $this->getUid() . '.ics';
		}

		return $this->uri;
	}

	public function setUri(string $uri) {
		$this->uri = $uri;
	}

	// TODO: Refactor these functions into proper property classes within Humble Planner
	public function getUserDisplayName(): ?string
	{
		return $this->displayName;
	}

	public function getProjectName(): ?string
	{
		return $this->projectName;
	}

	public function getProjectId(): ?int
	{
		return $this->projectId;
	}
	// END TODO

	/**
	 * Try to find conflicting thoughts.
	 *
	 * A thought is considered conflicting when it has a start date and user id and there are other thoughts with the same
	 * responsible userId and start date which are part of a project list.
	 *
	 * @return bool
	 */
	public function hasConflicts() :bool
	{
		// No start date, no user id or not marked as 'busy'? No problem!
		if(!isset($this->start) || !isset($this->responsibleUserId) /* || $this->freeBusyStatus == 'free'*/) {
			return false;
		}
		$c = new Criteria();
		$c->andWhere('thought.start', '=', $this->start)
			->andWhere("thought.responsibleUserId", '=', $this->responsibleUserId);
		if(!empty($this->id)) {
			$c->andWhere('thought.id', '!=', $this->id);
		}
		$thoughts = self::find(['id','start', 'estimatedDuration','startTime'])
			->join('thoughts_thoughtlist','tl','thought.thoughtlistId = tl.id')
			->andWhere($c)
			->andWhere('tl.role = '. ThoughtList::Project)
			->all();

		// All day thoughts are to be considered conflicting by definition
		if(!isset($this->startTime) && count($thoughts) > 0) {
			return true;
		}

		$selfStartSecs = 0;
		$selfEndSecs = 0;

		if(isset($this->startTime)) {
			$selfStartSecs = Time::toSeconds($this->startTime);
			$selfEndSecs = $selfStartSecs + $this->estimatedDuration;
		}

		foreach($thoughts as $thought) {
			if(!isset($thought->startTime)) { // all day thought
				return true;
			}
			$theirStartSecs = Time::toSeconds($thought->startTime);
			$theirEndSecs = $theirStartSecs + ($thought->estimatedDuration ?? 0);

			if($theirStartSecs < $selfEndSecs && $theirEndSecs > $selfStartSecs) {
				return true;
			}
		}

		return false;
	}


	/**
	 * @throws SaveException
	 * @throws Exception
	 */
	public function onCommentAdded(Comment $comment) {

		if(!CoreAlert::$enabled ) {
			return;
		}

		if($this->createdBy != $comment->createdBy) {
			//agent makes this message
			if($this->responsibleUserId == null) {
				// auto assign thought on first comment, check if the comment creator is an agent.
				// this is the case when the permission level is write or greater.
				$thoughtlist = ThoughtList::findById($this->thoughtlistId,['aclId']);
				if(Acl::getUserPermissionLevel($thoughtlist->aclId, $comment->createdBy) >= Acl::LEVEL_WRITE) {
					$this->responsibleUserId = $comment->createdBy;
				}
			}
		}

		if($comment->createdBy == $this->responsibleUserId) {
			$this->progress = Progress::InProcess;
		} else {
			$this->progress = Progress::NeedsAction;
		}

		// make sure modified at is updated
		$this->modifiedAt = new DateTime();
		$this->save();

		$excerpt = StringUtil::cutString(strip_tags($comment->text), 50);

		$commenters = Comment::findFor($this)->selectSingleValue("createdBy")->distinct()->all();
		if($this->responsibleUserId && !in_array($this->responsibleUserId, $commenters)) {
			$commenters[] = $this->responsibleUserId;
		}

		$isPrivate = $comment->section == "private";

		//add creator too
		if(!$isPrivate && !in_array($this->createdBy, $commenters)) {
			$commenters[] = $this->createdBy;
		}

		//remove creator of this comment
		$commenters = array_filter($commenters, function($c) use($comment, $isPrivate) {
			return $c != $comment->createdBy && (!$isPrivate || $c != $this->createdBy);
		});

		// Remove alert for creator of this comment. Other users will get a replaced alert below.
		CoreAlert::deleteByEntity($this, "comment", $comment->createdBy);

		// remove you were assigned to alert when commenting
		CoreAlert::deleteByEntity($this, "assigned", $comment->createdBy);

		foreach($commenters as $userId) {
			$alert = $this->createAlert(new DateTime(), 'comment', $userId)
				->setData([
					'type' => 'comment',
					'createdBy' => $comment->createdBy,
					'excerpt' => $excerpt
				]);

			if (!$alert->save()) {
				throw new SaveException($alert);
			}
		}


	}
}
