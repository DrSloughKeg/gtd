<?php


namespace go\modules\tutorial\gtd\install;

use go\core\model\User;
use go\modules\tutorial\gtd\model\Progress;
use go\modules\tutorial\gtd\model\ThoughtList;
use GO\Projects2\Model\ProjectEntity;
use go\core\util\DateTime;

class Migrator
{

	private function getProject(array $record) {
		if($record['project_id'] > 0) {
			$projectId = $record['project_id'];
			return ProjectEntity::findById($projectId);
		}
		return null;
	}
	public function job2thought()
	{
		echo "Migrating project jobs to thoughts" . PHP_EOL . PHP_EOL;
		// foreach pr2_thoughts record:
		$counter = 0;
		go()->getDbConnection()->beginTransaction();
		$query = go()->getDbConnection()
				->select('*')
				->from('pr2_thoughts');
		$stmt = $query->execute();

    while($record = $stmt->fetch()) {
      $counter++;
      $jobId = $record['id'];

      $thoughtlistId = null;
	    $project = $this->getProject($record);
      if($project) {
        $projectId = $project->id;

				// If for some reason there are more lists, just select the first list
				$prt = go()->getDbConnection()
					->select('id')
					->from('thoughts_thoughtlist')
					->where('projectId = ' . $projectId)
					->single();
				if ($prt) {
					$thoughtlistId = $prt['id'];
				} else {
					$arFlds = [
						'role' => ThoughtList::Project,
						'name' => $project->name,
						'createdBy' => $project->user_id,
						'aclId' => $project->findAclId(),
						'projectId' => $projectId,
						'version' => 1,
						'ownerId' => $project->user_id
					];
					go()->getDbConnection()->insert('thoughts_thoughtlist', $arFlds)->execute();
					$thoughtlistId = go()->getDbConnection()->getPDO()->lastInsertId();
				}

      } else {
        $counter++;
        echo 'S';
        if($counter % 50 === 0 ) {
	        echo PHP_EOL;
        }
        continue;
      }
      $due = $record['due_date'];
      if(!empty($due)) {
        $ts = new DateTime();
        $ts->setTimestamp($due);
      }
      $arFlds = [
        'uid' => \go\core\util\UUID::v4(),
        'thoughtlistId' => $thoughtlistId,
        'responsibleUserId' => $record['user_id'],
        'percentComplete' => $record['percentage_complete'],
        'estimatedDuration' => $record['duration'] * 60, //from minutes to seconds
        'progress' => $record['percentage_complete'] == 100 ? Progress::Completed : Progress::NeedsAction,
        'createdBy' => User::ID_SUPER_ADMIN,
        'modifiedBy' => User::ID_SUPER_ADMIN,
        'createdAt' => new DateTime(),
        'modifiedAt' => new DateTime(),
        'due' => !empty($due) ? $ts : null,
        'title' => $record['description'],
        'description' => ''
      ];
      if(!go()->getDbConnection()->insert('thoughts_thought', $arFlds)->execute()) {
        throw new \Exception("Que?");
      };
      $thoughtId = go()->getDbConnection()->getPDO()->lastInsertId();

      go()->getDbConnection()->update('pr2_hours', ['thought_id' => $thoughtId], ['thought_id' => $jobId])->execute();
      echo '.';
      if($counter % 50 === 0 ) {
        echo PHP_EOL;
      }
    }

		go()->getDbConnection()->commit();
		echo PHP_EOL . PHP_EOL . 'Done migrating project jobs to thoughts';
	}
}