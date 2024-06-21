set foreign_key_checks =0;
-- -----------------------------------------------------
-- Table `thoughts_thoughtlist`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `thoughts_thoughtlist` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `role` TINYINT(2) UNSIGNED NULL DEFAULT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) NULL,
  `createdBy` INT(11) default NULL,
  `aclId` INT(11) NOT NULL,
  `version` INT(10) UNSIGNED NOT NULL DEFAULT 1,
  `ownerId` INT(11) NOT NULL DEFAULT 1,
  `filesFolderId` INT(11) DEFAULT null,
  projectId int(11) null,
  groupingId int unsigned null,
  PRIMARY KEY (`id`),
  INDEX `fkCreatedBy` (`createdBy` ASC),
  INDEX `fkAcl` (`aclId` ASC),
  CONSTRAINT `thoughts_thoughtlist_ibfk1`
    FOREIGN KEY (`aclId`)
    REFERENCES `core_acl` (`id`),
  CONSTRAINT `thoughts_thoughtlist_ibfk2`
    FOREIGN KEY (`createdBy`)
    REFERENCES `core_user` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `thoughts_thought`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `thoughts_thought` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` VARCHAR(190) CHARACTER SET 'ascii' COLLATE 'ascii_bin' NOT NULL DEFAULT '',
  `thoughtlistId` INT(11) UNSIGNED NOT NULL,
  `groupId` INT UNSIGNED NULL DEFAULT NULL,
  `responsibleUserId` INT(11) DEFAULT NULL,
  `createdBy` INT(11) default NULL,
  `createdAt` DATETIME NOT NULL,
  `modifiedAt` DATETIME NOT NULL,
  `modifiedBy` INT(11) NULL DEFAULT null,
  `filesFolderId` INT(11) DEFAULT null,
  `due` DATE NULL,
  `start` DATE NULL,
   estimatedDuration int null comment 'Duration in seconds',
  `progress` TINYINT(2) NOT NULL DEFAULT 1,
  `progressUpdated` DATETIME NULL DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `color` CHAR(6) NULL,
  `recurrenceRule` VARCHAR(400) NULL DEFAULT NULL,
  `priority` INT(11) NOT NULL DEFAULT 1,
  `freeBusyStatus` CHAR(4) NULL DEFAULT 'busy',
  `privacy` VARCHAR(7) NULL DEFAULT 'public',
  `percentComplete` TINYINT(4) NOT NULL DEFAULT 0,
  `uri` VARCHAR(190) CHARACTER SET 'ascii' COLLATE 'ascii_bin' NULL DEFAULT NULL,
  `vcalendarBlobId` BINARY(40) NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `list_id` (`thoughtlistId` ASC),
  INDEX `rrule` (`recurrenceRule`(191) ASC),
  INDEX `uuid` (`uid` ASC),
  INDEX `fkModifiedBy` (`modifiedBy` ASC),
  INDEX `createdBy` (`createdBy` ASC),
  INDEX `filesFolderId` (`filesFolderId` ASC),
  INDEX `thoughts_thought_groupId_idx` (`groupId` ASC),
  INDEX `thoughts_vcalendar_blob_idx` (`vcalendarBlobId` ASC),
  CONSTRAINT `thoughts_thought_fkModifiedBy`
    FOREIGN KEY (`modifiedBy`)
    REFERENCES `core_user` (`id`)
    ON DELETE SET NULL,
  CONSTRAINT `thoughts_thought_ibfk_1`
    FOREIGN KEY (`thoughtlistId`)
    REFERENCES `thoughts_thoughtlist` (`id`) on delete cascade,
  CONSTRAINT `thoughts_thought_ibfk_2`
    FOREIGN KEY (`createdBy`)
    REFERENCES `core_user` (`id`)
    ON DELETE SET NULL,
  CONSTRAINT `thoughts_thought_groupId`
    FOREIGN KEY (`groupId`)
    REFERENCES `thoughts_thoughtlist_group` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `thoughts_vcalendar_blob`
    FOREIGN KEY (`vcalendarBlobId`)
    REFERENCES `core_blob` (`id`)
    ON DELETE RESTRICT
    ON UPDATE SET NULL,
  constraint thoughts_thought_core_user_id_fk
    foreign key (responsibleUserId)
    references core_user (id)
    on DELETE set null
    )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `thoughts_thought_user`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `thoughts_thought_user` (
  `thoughtId` INT(11) UNSIGNED NOT NULL,
  `userId` INT NOT NULL,
  `modSeq` INT NOT NULL DEFAULT 0,
  `freeBusyStatus` CHAR(4) NOT NULL DEFAULT 'busy',
  PRIMARY KEY (`thoughtId`, `userId`),
  INDEX `fk_thoughts_thought_user_thoughts_thought1_idx` (`thoughtId` ASC),
  CONSTRAINT `fk_thoughts_thought_user_thoughts_thought1`
    FOREIGN KEY (`thoughtId`)
    REFERENCES `thoughts_thought` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `thoughts_alert`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `thoughts_alert` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `when` DATETIME NOT NULL,
  `acknowledged` DATETIME DEFAULT NULL,
  `relatedTo` TEXT NULL,
  `action` SMALLINT(2) NOT NULL DEFAULT 1,
  `offset` VARCHAR(45) NULL,
  `relativeTo` VARCHAR(5) NULL DEFAULT 'start',
  `thoughtId` INT(11) UNSIGNED NOT NULL,
  `userId` INT NOT NULL,
  PRIMARY KEY (`id`, `thoughtId`, `userId`),
  INDEX `fk_thoughts_alert_thoughts_thought_user1_idx` (`thoughtId` ASC, `userId` ASC),
  CONSTRAINT `fk_thoughts_alert_thoughts_thought_user1`
    FOREIGN KEY (`thoughtId` , `userId`)
    REFERENCES `thoughts_thought_user` (`thoughtId` , `userId`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `thoughts_category`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `thoughts_category` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `ownerId` INT(11) NULL,
  `thoughtlistId` INT(11) UNSIGNED NULL,
  PRIMARY KEY (`id`),
  INDEX `user_id` (`ownerId` ASC),
  constraint thoughts_category_ibfk_1
      foreign key (ownerId) references core_user (id)
          on delete cascade,
  constraint thoughts_category_thoughtlist_ibfk_9
      foreign key (thoughtlistId) references thoughts_thoughtlist (id)
          on delete cascade)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;



-- -----------------------------------------------------
-- Table `thoughts_portlet_thoughtlist`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `thoughts_portlet_thoughtlist` (
  `userId` INT(11) NOT NULL,
  `thoughtlistId` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`userId`, `thoughtlistId`),
  INDEX `thoughtlistId` (`thoughtlistId` ASC),
  CONSTRAINT `thoughts_portlet_thoughtlist_ibfk_1`
    FOREIGN KEY (`userId`)
    REFERENCES `core_user` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `thoughts_portlet_thoughtlist_ibfk_2`
    FOREIGN KEY (`thoughtlistId`)
    REFERENCES `thoughts_thoughtlist` (`id`)
        ON DELETE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `thoughts_thought_category`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `thoughts_thought_category` (
  `thoughtId` INT(11) UNSIGNED NOT NULL,
  `categoryId` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`thoughtId`, `categoryId`),
  INDEX `thoughts_thought_category_ibfk_2` (`categoryId` ASC),
  CONSTRAINT `thoughts_thought_category_ibfk_1`
    FOREIGN KEY (`thoughtId`)
    REFERENCES `thoughts_thought` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `thoughts_thought_category_ibfk_2`
    FOREIGN KEY (`categoryId`)
    REFERENCES `thoughts_category` (`id`)
    ON DELETE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `thoughts_thought_custom_fields`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `thoughts_thought_custom_fields` (
  `id` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_thoughts_thought_custom_field1`
    FOREIGN KEY (`id`)
    REFERENCES `thoughts_thought` (`id`)
    ON DELETE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `thoughts_thoughtlist_group`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `thoughts_thoughtlist_group` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `color` CHAR(6) NULL,
  `sortOrder` SMALLINT(2) UNSIGNED NOT NULL DEFAULT 0,
  `thoughtlistId` INT(11) UNSIGNED NOT NULL,
  `progressChange` TINYINT(2) NULL,
  PRIMARY KEY (`id`, `thoughtlistId`),
  INDEX `fk_thoughts_column_thoughts_thoughtlist1_idx` (`thoughtlistId` ASC),
  CONSTRAINT `fk_thoughts_column_thoughts_thoughtlist1`
    FOREIGN KEY (`thoughtlistId`)
    REFERENCES `thoughts_thoughtlist` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `thoughts_thoughtlist_user`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `thoughts_thoughtlist_user` (
  `thoughtlistId` INT(11) UNSIGNED NOT NULL,
  `userId` INT NOT NULL,
  `modSeq` INT NOT NULL,
  `color` CHAR(6) NULL,
  `sortOrder` INT NULL,
  `isVisible` TINYINT(1) NOT NULL DEFAULT 0,
  `isSubscribed` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`thoughtlistId`, `userId`),
  INDEX `fk_thoughts_thoughtlist_user_thoughts_thoughtlist1_idx` (`thoughtlistId` ASC),
  CONSTRAINT `fk_thoughts_thoughtlist_user_thoughts_thoughtlist1`
    FOREIGN KEY (`thoughtlistId`)
    REFERENCES `thoughts_thoughtlist` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `thoughts_default_alert`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `thoughts_default_alert` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `when` DATETIME NOT NULL,
  `relatedTo` TEXT NULL,
  `action` SMALLINT(2) NOT NULL DEFAULT 1,
  `offset` VARCHAR(45) NULL,
  `relativeTo` VARCHAR(5) NULL DEFAULT 'start',
  `withTime` TINYINT(1) NOT NULL DEFAULT 1,
  `thoughtlistId` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`, `thoughtlistId`),
  INDEX `fk_thoughts_default_alert_thoughts_thoughtlist1_idx` (`thoughtlistId` ASC),
  CONSTRAINT `fk_thoughts_default_alert_thoughts_thoughtlist1`
    FOREIGN KEY (`thoughtlistId`)
    REFERENCES `thoughts_thoughtlist` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


create table thoughts_user_settings
(
    userId int(11) not null,
    defaultThoughtlistId int(11) unsigned null,
    rememberLastItems boolean not null default false,
    lastThoughtlistIds varchar(255) null,
    defaultDate bool default false null,
    constraint thoughts_user_settings_pk
        primary key (userId),
    constraint thoughts_user_settings_core_user_id_fk
        foreign key (userId) references core_user (id)
            on delete cascade
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

alter table thoughts_user_settings
    add constraint thoughts_user_settings_thoughts_thoughtlist_id_fk
        foreign key (defaultThoughtlistId) references thoughts_thoughtlist (id)
            on delete set null;

create index thoughts_thought_progress_index
    on thoughts_thought (progress);




create table thoughts_thoughtlist_grouping
(
    id      int unsigned auto_increment,
    name    varchar(190) not null,
    `order` int unsigned null,
    constraint thoughts_thoughtlist_grouping_pk
        primary key (id),
    constraint thoughts_thoughtlist_grouping_name
        unique (name)
);




alter table thoughts_thoughtlist
    add constraint thoughts_thoughtlist_thoughts_thoughtlist_grouping_null_fk
        foreign key (groupingId) references thoughts_thoughtlist_grouping (id)
            on delete set null;
