-- ##兼容SQL数据库脚本##
-- 开发(测试)环境，虚拟机自动初始化
-- * MySQL(MariadDB)数据库
--     [/*MYSQL]到[MYSQL*/]之间会只会导入mysql(MariadDB)数据库
-- * SQLite数据库(runtime/yyf.db)
--     [/*SQLITE]标签到[SQLITE*/]之间只会创建sqlite3数据库
-- * 标签之外sql为公用sql
-- * 注意：
--     - 文件UTF8格式，无BOM头保存
--     - 结束标签可以省略,缺省时到文件末尾
--     - 标签必须在一行开头前面无空格
--     - 可以有多对标签
--     - 如果不需要可以删除此文件
--     - 如果只用mysql可以定义在 mysql.sql中
/********************  下面定义SQL语句 ********************/
/*******************  DEFINE SQL CODE *******************/


/*MYSQL FOR MYSQ created_at DATABASE

DROP DATABASE IF EXISTS `yyf`;
CREATE DATABASE `yyf` 
  DEFAULT CHARACTER SET utf8
  DEFAULT COLLATE utf8_general_ci;

SET NAMES utf8;
USE `yyf`;

MYSQL*/


/*SQLITE -- FOR SQLITE
PRAGMA encoding="UTF-8";
SQLITE*/


/* 通用部分 */
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` INTEGER PRIMARY KEY,
  `account` char(16) NOT NULL,
  `name` char(16) NOT NULL,
  `home` char(64) DEFAULT NULL,
  `status` char(64) NOT NULL DEFAULT '1',
  `email` char(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

DROP TABLE IF EXISTS `project`;
CREATE TABLE `project` (
  `id` INTEGER PRIMARY KEY,
  `user_id` INTEGER,
  `name` char(16) NOT NULL,
  `home` char(64) DEFAULT NULL,
  `status` char(64) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
);

INSERT INTO `user` (`id`, `account`, `name`, `home`, `status`, `created_at`) VALUES
  (1, 'newfuture', 'New Future',	'https://github.com/NewFuture/',	'1',	'2016-08-25 07:16:33');
INSERT INTO `user` (`id`, `account`, `name`, `home`, `status`, `created_at`) VALUES 
  (2, '', '测试',	'',	'0',	'2016-08-25 07:17:21');

INSERT INTO `project` (`id`, `user_id`, `name`, `home`, `status`, `created_at`) VALUES
  (1, 1, 'yyf-book', 'https://github.com/NewFuture/yyf-book',	'1',	'2016-08-25 07:17:33');
INSERT INTO `project` (`id`, `user_id`, `name`, `home`, `status`, `created_at`) VALUES
  (2, 1, 'YYF-Debugger', 'https://github.com/NewFuture/YYF-Debugger',	'1',	'2016-08-25 07:18:21');


/*MYSQL
-- MySQL主键自增
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
ALTER TABLE `user` CHANGE `id` `id` INTEGER NOT NULL AUTO_INCREMENT;
ALTER TABLE `project` CHANGE `id` `id` INTEGER NOT NULL AUTO_INCREMENT;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
-- InnoDB
ALTER TABLE `user` ENGINE=InnoDB;
ALTER TABLE `project` ENGINE=InnoDB;
ALTER TABLE `project` ADD FOREIGN KEY (user_id) REFERENCES `user`(`id`);
MYSQL*/
