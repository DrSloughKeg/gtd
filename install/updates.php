<?php

use go\modules\tutorial\gtd\install\Migrator;

$updates['201911061630'][] = function() {
	\go\core\db\Utils::runSQLFile(\GO()->getEnvironment()->getInstallFolder()->getFile("go/modules/community/thoughts/install/upgrade.sql"));
};

$updates['201911061630'][] = function(){
	$stmt =\GO::getDbConnection()->query("SELECT id, rrule,`start_time` FROM ta_thoughts WHERE rrule != ''");

	while($row = $stmt->fetch()) {
		try {
			$rrule = \go\core\util\Recurrence::fromString($row['rrule'], new DateTime("@" . $row["start_time"]));
			$data = ['recurrenceRule' => json_encode($rrule->toArray())];
			go()->getDbConnection()->updateIgnore('thoughts_thought', $data, ['id' => $row['id']])->execute();
		} catch(Exception $e) {
			echo "RRULE Exception:  " . $e->getMessage() ."\n";
		}
	}
};

// insert function
$updates['201911061630'][] = function(){
// MS: Is this code still relevant?

//	$stmt =\GO::getDbConnection()->query("SELECT * FROM ta_thoughts");
//
//	while($row = $stmt->fetch()) {
//		$needles = ["COUNT","UNTIL","INTERVAL","FREQ","BYDAY"];
//		$haystack = ["count","until","interval","frequency","byDay"];
//		$data = [];
//		$data['recurrenceRule'] = str_replace($needles,$haystack,$row["rrule"]);
//
//		$rrule = new Rrule();
//		$rrule->readIcalendarRruleString($row["start_time"], $row['rrule']);
//
//		$days = $rrule->byday;
//		$newDays = [];
//		foreach($days as $day) {
//			$day = str_replace($rrule->bysetpos,"",$day);
//			$newDays[] = ['day' => $day, 'position' => $rrule->bysetpos];
//		}
//
//		$rrule->byday = $newDays;
//
//		$recurrencePattern = [
//			'frequency' => $rrule->freq,
//			'bySetPosition' => $rrule->bysetpos,
//			'interval' => $rrule->interval,
//			'byDay' =>  $rrule->byday
//		];
//
//		if($rrule->until) {
//			$rrule->until = DateTime::createFromFormat( 'U', $rrule->until);
//			$recurrencePattern['until'] = $rrule->until;
//		} else {
//			$recurrencePattern['count'] = $rrule->count;
//		}
//
//		$data['recurrenceRule'] = json_encode($recurrencePattern);
//		GO()->getDbConnection()->insertIgnore('thoughts_thought', $data)->execute();
//	}

};

$updates['202101011630'][] = "ALTER TABLE `thoughts_thought` CHANGE COLUMN `description` `description` TEXT NULL DEFAULT '';";
$updates['202104301506'][] = function() {

	if(\go\core\model\Module::isInstalled('legacy', 'projects2')) {
		$m = new Migrator();
		$m->job2thought();
	}
};

$updates['202105211543'][] = "ALTER TABLE `thoughts_thought`  ADD `progressChange` TINYINT(2) NULL";

$updates['202106011409'][] = "ALTER TABLE `thoughts_thought` ADD COLUMN `startTime` TIME NULL DEFAULT NULL";





$updates['202106101432'][] = "alter table thoughts_thoughtlist
	add projectId int null;";

$updates['202106181401'][] = "create table if not exists thoughts_user_settings
(
    userId int not null,
    defaultThoughtlistId int(11) unsigned null,
    rememberLastItems boolean not null default false,
    lastThoughtlistIds varchar(255) null,
    constraint thoughts_user_settings_pk
        primary key (userId),
    constraint thoughts_user_settings_core_user_id_fk
        foreign key (userId) references core_user (id)
            on delete cascade
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";


$updates['202106181401'][] = "alter table thoughts_thought drop foreign key thoughts_thought_ibfk_1;";

$updates['202106181401'][] = "alter table thoughts_thought
	add constraint thoughts_thought_ibfk_1
		foreign key (thoughtlistId) references thoughts_thoughtlist (id)
			on DELETE cascade;";

$updates['202106181401'][] = "alter table thoughts_user_settings
	add constraint thoughts_user_settings_thoughts_thoughtlist_id_fk
		foreign key (defaultThoughtlistId) references thoughts_thoughtlist (id)
			on delete set null;";

$updates['202107051416'][] = "create index thoughts_thought_progress_index
	on thoughts_thought (progress);";

$updates['202107251024'][] = "ALTER TABLE `thoughts_category` DROP FOREIGN KEY `thoughts_category_ibfk_1`;";
$updates['202107251024'][] = "ALTER TABLE `thoughts_category` CHANGE COLUMN `createdBy` `ownerId` INT(11) NULL ;";
$updates['202107251024'][] = "ALTER TABLE `thoughts_category` ADD CONSTRAINT `thoughts_category_ibfk_1` FOREIGN KEY (`ownerId`) REFERENCES `core_user` (`id`);";

$updates['202108101005'][] = "ALTER TABLE `thoughts_thought` ADD COLUMN `location` TEXT NULL;";

$updates['202109301005'][] = "ALTER TABLE `thoughts_category`
ADD COLUMN `thoughtlistId` INT(11) NULL DEFAULT NULL AFTER `ownerId`,
ADD INDEX `thoughts_category_thoughtlist_ibfk_9_idx` (`thoughtlistId` ASC);";

$updates['202109301006'][] = "ALTER TABLE `thoughts_category`
ADD CONSTRAINT `thoughts_category_thoughtlist_ibfk_9`
  FOREIGN KEY (`thoughtlistId`)
  REFERENCES `thoughts_thoughtlist` (`createdBy`)
  ON DELETE CASCADE;";


$updates['202111251126'][] = "alter table thoughts_category drop foreign key thoughts_category_ibfk_1;";

$updates['202111251126'][] = "alter table thoughts_category
	add constraint thoughts_category_ibfk_1
		foreign key (ownerId) references core_user (id)
			on delete cascade;";

$updates['202201261056'][] = "ALTER TABLE `thoughts_portlet_thoughtlist` DROP FOREIGN KEY `thoughts_portlet_thoughtlist_ibfk_1`;";
$updates['202201261056'][] = "ALTER TABLE `thoughts_portlet_thoughtlist` CHANGE COLUMN `createdBy` `userId` INT(11) NOT NULL ;";
$updates['202201261056'][] = "ALTER TABLE `thoughts_portlet_thoughtlist` ADD CONSTRAINT `thoughts_portlet_thoughtlist_ibfk_1` FOREIGN KEY (`userId`)  REFERENCES `core_user` (`id`) ON DELETE CASCADE;";

$updates['202201271056'][] = "delete from thoughts_thought_category where categoryId not in (select id from thoughts_category)";

$updates['202202041432'][] = "alter table thoughts_category
    drop foreign key thoughts_category_thoughtlist_ibfk_9;";

$updates['202202041432'][] = "alter table thoughts_category
    modify thoughtlistId int(11) unsigned null;";

$updates['202202041432'][] = "alter table thoughts_category
    add constraint thoughts_category_thoughtlist_ibfk_9
        foreign key (thoughtlistId) references thoughts_thoughtlist (id)
            on delete cascade;";

$updates['202202081432'][] = "ALTER TABLE `thoughts_thought` CHANGE COLUMN `description` `description` TEXT NULL DEFAULT null;";

$updates['202202241617'][] = "alter table thoughts_user_settings
    add defaultDate bool default false null;";



$updates['202205101237'][] = "update thoughts_thought set filesFolderId = null where filesFolderId=0;";


$updates['202205311153'][] = "update thoughts_thought set responsibleUserId = null where responsibleUserId not in (select id from core_user);";

$updates['202205311153'][] = "alter table thoughts_thought
    add constraint thoughts_thought_core_user_id_fk
        foreign key (responsibleUserId) references core_user (id)
            on delete set null;";

$updates['202206031355'][] = 'ALTER TABLE `thoughts_thought` ADD COLUMN `latitude` decimal(10,8) DEFAULT NULL, ' .
	'ADD COLUMN `longitude` decimal(11,8) DEFAULT NULL;';

$updates['202206201417'][] = 'alter table thoughts_thoughtlist_group
    add progressChange tinyint(2) null;';

$updates['202301301230'][] = function () {
	if (\go\core\model\Module::isInstalled('legacy', 'projects2')) {
		echo "Cleaning up orphaned project lists..." . PHP_EOL;
		$q = "DELETE FROM `thoughts_thoughtlist` WHERE `role` = 3 AND `projectId` NOT IN(SELECT `id` FROM `pr2_projects`);";
		go()->getDbConnection()->exec($q);
	}
};




//6.7

$updates['202301301230'][] = "alter table thoughts_thought
	add aclId int null;";

$updates['202301301230'][] = "update thoughts_thought t set t.aclId = (select aclId from thoughts_thoughtlist where id = t.thoughtlistId);";

$updates['202301301230'][] = "alter table thoughts_thought
	add constraint thoughts_thought_core_acl_id_fk
		foreign key (aclId) references core_acl (id)  ON DELETE RESTRICT;";


$updates['202301301230'][] = "update core_entity set name='ThoughtList', clientName='ThoughtList' where name='Thoughtlist'";


$updates['202301301230'][] = "create table thoughts_thoughtlist_grouping
(
	id      int unsigned auto_increment,
    name    varchar(190) not null,
    `order` int unsigned null,
    constraint thoughts_thoughtlist_grouping_pk
        primary key (id),
    constraint thoughts_thoughtlist_grouping_name
        unique (name)
);";


$updates['202301301230'][] = "alter table thoughts_thoughtlist
                    add groupingId int unsigned null;";

$updates['202301301230'][] = "alter table thoughts_thoughtlist
                    add constraint thoughts_thoughtlist_thoughts_thoughtlist_grouping_null_fk
                        foreign key (groupingId) references thoughts_thoughtlist_grouping (id)
                            on delete set null;";
$updates['202305231613'][] = "ALTER TABLE `thoughts_thought` DROP FOREIGN KEY `thoughts_thought_core_acl_id_fk`;";
$updates['202305231613'][] = "ALTER TABLE `thoughts_thought` DROP COLUMN `aclId`, DROP INDEX `thoughts_thought_core_acl_id_fk` ;";
