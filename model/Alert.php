<?php
/**
 * @copyright (c) 2019, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace go\modules\tutorial\gtd\model;

use DateTimeInterface;
use go\core\orm\Mapping;
use go\core\orm\Property;
use go\core\orm\UserProperty;
use go\core\util\DateTime;

/**
 * Representing alerts/reminders to display or send to the user for this calendar object.
 */
class Alert extends UserProperty
{

	const ActionDisplay = 1;
	const ActionEmail = 2;

	/** @var int PK */
	public $id;

	/** @var int PK to thought this Alert belongs to */
	protected $thoughtId;

	/** @var DateTime This records when an alert was last acknowledged. This is set when the user has dismissed the alert */
	public $acknowledged;
	/**
	 * @var string[Relation]
	 * Relates this alert to other alerts in the same thought.
	 * If the user wishes to snooze an alert, the application
	 * creates an alert to trigger after snoozing.
	 * Relation type: 'first', 'next', 'child', 'parent
	 * eg. [5 => {relation: {'parent':true,'next':true}}]
	 */
	public $relatedTo;
	/**
	 * Defines a specific UTC date-time when the alert is triggered.
	 * Wen using a OffsetTrigger this will still be set
	 * @var DateTime
	 */
	protected $when; // start or due time of thought
	/** @var If set trigger is not absolute and should change when start or end time change */
	protected $offset;

	protected $relativeTo = 'start';

	/** @var string 'display' or 'email' */
	protected $action = self::ActionDisplay;

	protected static function defineMapping(): Mapping
	{
		return parent::defineMapping()->addTable("thoughts_alert", "alert");
	}

	public function getAction()
	{
		return $this->action === self::ActionDisplay ? 'display' : 'email';
	}

	public function setAction($value)
	{
		switch ($value) {
			case 'display':
				$this->action = self::ActionDisplay;
				break;
			case 'email':
				$this->action = self::ActionEmail;
				break;
			default:
				$this->action = self::ActionDisplay;
		}
	}

	public function getTrigger()
	{
		if (isset($this->offset)) {
			return [
				'offset' => $this->offset,
				'relativeTo' => $this->relativeTo ?? 'start'
			];
		}
		if (isset($this->when)) {
			return ['when' => $this->when];
		}
		return null;
	}

	/**
	 * Returns the time this alert occuurs
	 *
	 * @param Thought $thought
	 * @return DateTime|null
	 */
	public function at(Thought $thought)
	{
		if (isset($this->offset) && $thought) {
			$offset = $this->offset;
			$date = ($this->relativeTo == 'due') ? clone $thought->due : clone $thought->start;
			if ($offset[0] == '-') {
				$date->sub(new DateInterval(substr(1, $offset)));
				return $date;
			} else if ($offset[0] == '+') {
				$offset = substr(1, $offset);
			}
			$date->add(new DateInterval($offset));
			return $date;
		}
		if (isset($this->when)) {
			return $this->when;
		}
		return null;
	}

	/**
	 * OffsetTrigger
	 *  - offset = SignedDuration Defines the offset at which to trigger the alert relative to
	 *      the time property defined in the "relativeTo" property of the alert
	 *  - relativeTo = 'start' or 'end'
	 * AbsoluteTrigger
	 *  - when DateTime Defines a specific UTC date-time when the alert is triggered.
	 * @params $value OffsetTrigger|AbsoluteTrigger
	 */
	public function setTrigger($value)
	{
		if (isset($value['offset'])) {
			$thought = Thought::findById($this->thoughtId);
			$this->offset = $value['offset'];
			$this->relativeTo = $value['relativeTo'];
			$relDate = clone($value['relativeTo'] === 'end' ? $thought->due : $thought->start);
			$this->when = $relDate->add(new \DateInterval($value['offset']));
		}
		if (isset($value['when'])) {
			$this->offset = null;
			$this->relativeTo = null;
			$this->when = new DateTime($value['when']);
			$this->when->setTimeZone(new \DateTimeZone("UTC"));
		}
	}

	/**
	 * Set specific alarm time
	 *
	 * @param DateTime $when
	 * @return $this
	 */
	public function when(DateTimeInterface $when) {
		$this->offset = null;
		$this->relativeTo = null;
		$this->when = $when;

		return $this;
	}

}