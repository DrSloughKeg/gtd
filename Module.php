<?php
/**
 * @copyright (c) 2019, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
namespace go\modules\tutorial\gtd;
							
use Exception;
use Faker\Generator;
use go\core;
use go\core\model;
use go\core\model\Group;
use go\core\model\Link;
use go\core\model\Permission;
use go\core\model\User;
use go\core\orm\exception\SaveException;
use go\core\orm\Mapping;
use go\core\orm\Property;
use go\modules\community\comments\Module as CommentsModule;
use go\modules\tutorial\gtd\model\Thought;
use go\modules\tutorial\gtd\model\ThoughtList;
use go\modules\tutotial\gtd\model\UserSettings;
use GO\Projects2\Model\Project;

class Module extends core\Module {
	/**
	 * The development status of this module
	 * @return string
	 */
	public function getStatus() : string{
		return self::STATUS_STABLE;
	}

	public function getAuthor(): string
	{
		return "Benjamin Pye <bpye@critias.ca>";
	}

	public function autoInstall(): bool
	{
		return true;
	}

	public function defineListeners()
	{
		User::on(Property::EVENT_MAPPING, static::class, 'onMap');
		User::on(User::EVENT_BEFORE_DELETE, static::class, 'onUserDelete');
		User::on(User::EVENT_BEFORE_SAVE, static::class, 'onUserBeforeSave');
	}


	public function initListeners()
	{
		if(model\Module::isInstalled('legacy', 'projects2')) {
			$prj = new Project();
			$prj->addListener('beforedelete', self::class, 'onBeforeProjectDelete');
		}
	}



	public static function onMap(Mapping $mapping) {
		$mapping->addHasOne('thoughtsSettings', UserSettings::class, ['id' => 'userId'], true);
		$mapping->addScalar('thoughtPortletTHoughtLists', "thoughts_portlet_thoughtlist", ['id' => 'userId']);
	}

	protected function rights(): array
	{
		return [
			'mayChangeThoughtlists', // allows Thoughtlist/set (hide ui elements that use this)
			'mayChangeCategories', // allows creating global  categories for everyone. Personal cats can always be created.
		];
	}

	protected function beforeInstall(model\Module $model): bool
	{
		// Share module with Internal group
		$model->permissions[Group::ID_INTERNAL] = (new Permission($model))
			->setRights(['mayRead' => true, 'mayChangeThoughtlists' => true, 'mayChangeCategories' => false]);

		return parent::beforeInstall($model);
	}


	/**
	 * @throws Exception
	 */
	public static function onUserDelete(core\db\Query $query) {
		ThoughtList::delete(['createdBy' => $query]);
	}


	/**
	 * @throws Exception
	 */
	public static function onBeforeProjectDelete(Project $project): bool
	{
		$query = (new core\orm\Query())->where(['role' => Thoughtlist::Project, 'projectId' => $project->id]);
		$success = ThoughtList::delete($query);
		return $success;
	}


	public static function onUserBeforeSave(User $user)
	{
		if (!$user->isNew() && $user->isModified('displayName')) {
			$oldName = $user->getOldValue('displayName');
			$thoughtlist = ThoughtList::find()->where(['createdBy' => $user->id, 'name' => $oldName])->single();
			if ($thoughtlist) {
				$thoughtlist->name = $user->displayName;
				$thoughtlist->save();
			}
		}
	}

	public function demo(Generator $faker)
	{
		$thoughtlists = ThoughtList::find()->where(['role' => ThoughtList::List]);

		foreach($thoughtlists as $thoughtlist) {
			$this->demoThoughts($faker, $thoughtlist);
		}
	}

	/**
	 * @throws SaveException
	 * @throws Exception
	 */
	public function demoThoughts(Generator $faker, ThoughtList $thoughtlist, bool $withLinks = true, $titles = null, $count = 5) {

		if(!isset($titles)) {
			$titles = [
				"Finish thoughts module",
				"Call Michael about Energy project",
				"Order printer paper",
				"Create functional design",
				"Create technical design",
				"Create database design",
				"Order machine parts",
				"Order lunch",
				"Schedule meeting with client",
				"Discuss design with John",
				"Fix issue with automatic problem solver",
				"Prepare Weekly board meeting",
				"Test two factor authentication",
				"Perform weekly penetration tests on Group-Office",
				"Implement Oauth 2.0",
				"Implement Open ID",
				"Feature request on autofill email addresses",
				"Feature request SMIME encryption",
				"Discuss roadmap for next release",
				"Buy bigger screens",
				"Verify backups",
				"Perform weekly penetration tests on servers",
				"Prepare quote for solar panels module",
				"Prepare quote for Wind mill project",
				"Review graphical designs for Group-Office website",
				"Design checkout process",
				"Take out the trash",
				"Order more coffee",
			];
		}

		$titleCount = count($titles);

		$userIds = User::find()->selectSingleValue('id')->all();
		$maxUserIndex = count($userIds) - 1;


		for($i = 0; $i < $count; $i ++ ) {
			echo ".";
			$thought = new Thought();
			$thought->title = $titles[$faker->numberBetween(0, $titleCount - 1)];
			$thought->createdBy = $userIds[$faker->numberBetween(0, $maxUserIndex)];
			$thought->responsibleUserId = $userIds[$faker->numberBetween(0, $maxUserIndex)];
			$thought->start = $faker->dateTimeBetween("-1 years", "now");
			$thought->due =  $faker->dateTimeBetween($thought->start, "now");
			$thought->thoughtlistId = $thoughtlist->id;
			$thought->percentComplete = $faker->randomElement([0, 20, 50, 80, 100]);

			$thought->createdAt = $faker->dateTimeBetween("-1 years", "now");
			$thought->modifiedAt = $faker->dateTimeBetween($thought->createdAt, "now");

			if(!$thought->save()) {
				throw new SaveException($thought);
			}

			if($withLinks && core\model\Module::isInstalled("community", "comments")) {
				CommentsModule::demoComments($faker, $thought);
			}

			if($withLinks) {
				Link::demo($faker, $thought);
			}
		}
	}


}