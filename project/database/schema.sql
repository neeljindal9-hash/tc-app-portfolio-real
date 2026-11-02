SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE `api_rate_limits` (
  `user_id` int(11) NOT NULL,
  `endpoint_type` varchar(20) NOT NULL,
  `tokens` int(11) NOT NULL,
  `last_updated` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `classes` (
  `ID` smallint(6) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `teacher` varchar(50) DEFAULT NULL,
  `convo_id` int(11) DEFAULT NULL COMMENT 'corresponding conversation for discussion',
  `approved` tinyint(1) DEFAULT 0,
  `room` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `conversations` (
  `ID` int(11) NOT NULL,
  `name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `messages_updated` timestamp NULL DEFAULT current_timestamp() COMMENT 'last time 1st layer message from ID created',
  `access_mode` tinyint(2) UNSIGNED NOT NULL DEFAULT 4 COMMENT '0- fully enabled\r\n1- read-only\r\n2- disabled until minimum requirement reached\r\n3- disabled: lock down\r\n4- disabled: default/unknown ',
  `lobby_state` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `plaza_state` smallint(6) UNSIGNED NOT NULL DEFAULT 0,
  `rankings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`rankings`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `friends` (
  `user_id` int(11) NOT NULL,
  `friend_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `lobby_components` (
  `ID` int(11) NOT NULL,
  `convo_id` int(11) NOT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`description`)),
  `author_id` int(11) DEFAULT NULL,
  `approvers_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT json_array() CHECK (json_valid(`approvers_ids`)),
  `type` enum('homework','schedule','test','class') NOT NULL,
  `end_date` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `approval_status` int(11) NOT NULL DEFAULT 0 COMMENT 'represents how verified the component is by peers'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `lobby_data` (
  `lobby_ref_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `data_string` tinytext DEFAULT NULL,
  `data_int` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `lobby_items` (
  `ID` int(11) NOT NULL,
  `convo_id` int(11) NOT NULL,
  `type` enum('schedule','test','class','homework') NOT NULL COMMENT 'homework, test, class, schedule',
  `corrob_mode` varchar(25) NOT NULL,
  `group_id` int(11) NOT NULL,
  `item_descriptor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'array of descriptor associated with what this data does, for the computer to understand and display' CHECK (json_valid(`item_descriptor`)),
  `date` date NOT NULL COMMENT 'YYYY/MM/DD',
  `up_to_date` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'whether it doesn''t need to be recalculated.',
  `current_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`current_value`)),
  `is_int_data` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='stores references to sections of the lobby by component ID';

CREATE TABLE `messages` (
  `ID` int(11) NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_last` timestamp NOT NULL DEFAULT current_timestamp(),
  `group_state` smallint(6) NOT NULL DEFAULT 0,
  `convo_id` int(11) NOT NULL,
  `structure` enum('discussion','debate','thoughts') DEFAULT NULL,
  `coalesced_par_id` int(11) GENERATED ALWAYS AS (coalesce(`parent_id`,`convo_id`)) VIRTUAL COMMENT '"effective" parent_id',
  `path` varchar(100) NOT NULL,
  `isAnonymous` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `users` (
  `ID` int(11) NOT NULL,
  `name` varchar(31) NOT NULL,
  `email` varchar(250) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `warnings` tinyint(4) NOT NULL DEFAULT 0,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `welcome_status` enum('in_progress','not_started','complete') NOT NULL DEFAULT 'not_started',
  `school_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `users_classes` (
  `user_id` int(11) NOT NULL,
  `class_id` smallint(6) DEFAULT NULL COMMENT 'Ex: Smith-Biology',
  `period` tinyint(4) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `users_conversations` (
  `user_id` int(11) NOT NULL,
  `convo_id` int(11) NOT NULL,
  `local_state` smallint(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'for updates only for user to see in their conversation',
  `old_points` int(11) NOT NULL DEFAULT 0 COMMENT ' how many old points they had',
  `new_points` int(11) NOT NULL DEFAULT 0 COMMENT 'how many new points to function as a limit',
  `points` int(11) GENERATED ALWAYS AS (`new_points` + `old_points`) VIRTUAL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `user_profile` (
  `ID` int(11) NOT NULL,
  `grade` tinyint(4) DEFAULT NULL,
  `middle_school` varchar(80) DEFAULT NULL,
  `birthday` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `user_stats` (
  `user_id` int(11) NOT NULL,
  `rel_1` decimal(6,5) NOT NULL DEFAULT 0.20000,
  `rel_2` decimal(6,5) NOT NULL DEFAULT 0.30000,
  `rel_3` decimal(6,5) NOT NULL DEFAULT 0.00000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


ALTER TABLE `api_rate_limits`
  ADD PRIMARY KEY (`user_id`,`endpoint_type`),
  ADD KEY `idx_user_endpoint` (`user_id`,`endpoint_type`);

ALTER TABLE `classes`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `classes_foriegnKey_conversations` (`convo_id`);

ALTER TABLE `conversations`
  ADD PRIMARY KEY (`ID`);

ALTER TABLE `friends`
  ADD PRIMARY KEY (`user_id`,`friend_id`),
  ADD KEY `friend_id` (`friend_id`);

ALTER TABLE `lobby_components`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `convo_id` (`convo_id`);

ALTER TABLE `lobby_data`
  ADD PRIMARY KEY (`lobby_ref_id`,`user_id`) USING BTREE,
  ADD KEY `users_id_fk+index` (`user_id`);

ALTER TABLE `lobby_items`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `convo_id_idx+fk` (`convo_id`),
  ADD KEY `group/comp_id_fk` (`group_id`);

ALTER TABLE `messages`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `messages_ibfk_users1` (`user_id`),
  ADD KEY `messages_ibfk_convo2` (`convo_id`),
  ADD KEY `messages_ibfk_parId` (`parent_id`),
  ADD KEY `co_par_id_ID` (`coalesced_par_id`,`ID`) USING BTREE,
  ADD KEY `path_structure_ID` (`path`,`structure`,`ID`) USING BTREE;

ALTER TABLE `users`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `school_id` (`school_id`);

ALTER TABLE `users_classes`
  ADD PRIMARY KEY (`user_id`,`period`) USING BTREE,
  ADD UNIQUE KEY `unique:userID->ClassID` (`class_id`,`user_id`) USING BTREE,
  ADD KEY `class_id_period_time` (`class_id`,`period`,`joined_at`) USING BTREE;

ALTER TABLE `users_conversations`
  ADD PRIMARY KEY (`user_id`,`convo_id`) USING BTREE,
  ADD KEY `ForeignKey:ConvoID` (`convo_id`);

ALTER TABLE `user_profile`
  ADD PRIMARY KEY (`ID`);

ALTER TABLE `user_stats`
  ADD PRIMARY KEY (`user_id`);


ALTER TABLE `classes`
  MODIFY `ID` smallint(6) NOT NULL AUTO_INCREMENT;

ALTER TABLE `conversations`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `lobby_components`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `lobby_items`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `messages`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT 5001;

ALTER TABLE `users`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;


ALTER TABLE `api_rate_limits`
  ADD CONSTRAINT `ForiegnKey_userID` FOREIGN KEY (`user_id`) REFERENCES `users` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `classes`
  ADD CONSTRAINT `classes_foriegnKey_conversations` FOREIGN KEY (`convo_id`) REFERENCES `conversations` (`ID`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `friends`
  ADD CONSTRAINT `friends_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `friends_ibfk_2` FOREIGN KEY (`friend_id`) REFERENCES `users` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `lobby_components`
  ADD CONSTRAINT `convo_id_FK` FOREIGN KEY (`convo_id`) REFERENCES `conversations` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `lobby_data`
  ADD CONSTRAINT `ref_id_fk_toLobby` FOREIGN KEY (`lobby_ref_id`) REFERENCES `lobby_items` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_id_fk_toUsers` FOREIGN KEY (`user_id`) REFERENCES `users` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `lobby_items`
  ADD CONSTRAINT `convo_id_idx+fk` FOREIGN KEY (`convo_id`) REFERENCES `conversations` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `group/comp_id_fk` FOREIGN KEY (`group_id`) REFERENCES `lobby_components` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_convo` FOREIGN KEY (`convo_id`) REFERENCES `conversations` (`ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `messages_ibfk_parId` FOREIGN KEY (`parent_id`) REFERENCES `messages` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `messages_ibfk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`ID`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `users_classes`
  ADD CONSTRAINT `ForeignKey:ClassID` FOREIGN KEY (`class_id`) REFERENCES `classes` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ForeignKey:UserID2` FOREIGN KEY (`user_id`) REFERENCES `users` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `users_conversations`
  ADD CONSTRAINT `ForeignKey:ConvoID` FOREIGN KEY (`convo_id`) REFERENCES `conversations` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ForeignKey:UserID` FOREIGN KEY (`user_id`) REFERENCES `users` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `user_profile`
  ADD CONSTRAINT `userID-FKtoparent` FOREIGN KEY (`ID`) REFERENCES `users` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `user_stats`
  ADD CONSTRAINT `userId-FK_toparent` FOREIGN KEY (`user_id`) REFERENCES `users` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
