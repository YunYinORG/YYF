SET NAMES utf8;
SET time_zone = '+00:00';
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP SCHEMA IF EXISTS `yyf`;
CREATE SCHEMA IF NOT EXISTS `yyf` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
USE `yyf`;

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account` char(16) NOT NULL,
  `name` char(16) NOT NULL,
  `home` char(64) DEFAULT NULL,
  `status` char(64) NOT NULL DEFAULT '1',
  `email` char(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `project`;
CREATE TABLE `project` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11),
  `name` char(16) NOT NULL,
  `home` char(64) DEFAULT NULL,
  `status` char(64) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `project_creater` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `user` (`id`, `account`, `name`, `home`, `status`, `created_at`, `updated_at`) VALUES
(1, 'newfuture', 'New Future',	'https://github.com/NewFuture/',	'1',	'2016-08-25 07:16:33',	'2016-08-25 07:16:33'),
(2, '', '测试',	'',	'0',	'2016-08-25 07:17:21',	'2016-08-25 07:17:21');

INSERT INTO `project` (`id`, `user_id`, `name`, `home`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'yyf-book', 'https://github.com/NewFuture/yyf-book',	'1',	'2016-08-25 07:17:33',	'2016-08-25 07:17:33'),
(2, 1, 'YYF-Debugger', 'https://github.com/NewFuture/YYF-Debugger',	'1',	'2016-08-25 07:18:21',	'2016-08-25 07:18:21');

