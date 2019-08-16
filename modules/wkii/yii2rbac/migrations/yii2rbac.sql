# ************************************************************
# Sequel Pro SQL dump
# Version 4529
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 127.0.0.1 (MySQL 5.5.44)
# Database: rbac
# Generation Time: 2016-02-27 09:55:21 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table yii2rbac_auth_item
# ------------------------------------------------------------

DROP TABLE IF EXISTS `yii2rbac_auth_item`;

CREATE TABLE `yii2rbac_auth_item` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'item id',
  `item_name` varchar(100) NOT NULL COMMENT '命名空间格式',
  `platform_id` tinyint(2) unsigned NOT NULL DEFAULT '1',
  `module` varchar(50) NOT NULL DEFAULT '' COMMENT 'module name',
  `controller` varchar(50) NOT NULL DEFAULT '' COMMENT 'Controller name',
  `action` varchar(45) NOT NULL DEFAULT '' COMMENT 'Action name',
  `description` varchar(100) NOT NULL DEFAULT '' COMMENT 'item description',
  `type` tinyint(2) unsigned NOT NULL DEFAULT '1' COMMENT '1:operation, 2:custom 3:data',
  `allowed` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT 'allowed for no guest',
  `bizrule` varchar(200) NOT NULL DEFAULT '' COMMENT 'rule, php code',
  PRIMARY KEY (`item_id`),
  UNIQUE KEY `idx_name_platform` (`item_name`,`platform_id`) USING BTREE,
  KEY `idx_module` (`module`) USING BTREE,
  KEY `idx_controller` (`controller`) USING BTREE,
  KEY `idx_action` (`action`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='auth items';



# Dump of table yii2rbac_auth_task
# ------------------------------------------------------------

DROP TABLE IF EXISTS `yii2rbac_auth_task`;

CREATE TABLE `yii2rbac_auth_task` (
  `task_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'task id',
  `task_name` varchar(64) NOT NULL DEFAULT '' COMMENT 'task name',
  `task_category_id` int(11) NOT NULL DEFAULT '0' COMMENT 'task category id',
  `description` varchar(200) NOT NULL DEFAULT '' COMMENT 'description',
  PRIMARY KEY (`task_id`),
  UNIQUE KEY `task_name` (`task_name`),
  KEY `category_id` (`task_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='rbac task table';



# Dump of table yii2rbac_role
# ------------------------------------------------------------

DROP TABLE IF EXISTS `yii2rbac_role`;

CREATE TABLE `yii2rbac_role` (
  `role_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'rule id',
  `role_name` varchar(45) NOT NULL COMMENT 'role name',
  `description` varchar(200) NOT NULL DEFAULT '' COMMENT 'description',
  `weight` int(11) NOT NULL COMMENT '权重',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT 'status 1:active, 0:invalid',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'create time',
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `roler_name_UNIQUE` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='role';



# Dump of table yii2rbac_role_task
# ------------------------------------------------------------

DROP TABLE IF EXISTS `yii2rbac_role_task`;

CREATE TABLE `yii2rbac_role_task` (
  `role_id` int(11) unsigned NOT NULL COMMENT 'role id',
  `task_id` int(11) unsigned NOT NULL COMMENT 'task id',
  UNIQUE KEY `role_task` (`role_id`,`task_id`),
  KEY `role_id` (`role_id`),
  KEY `task_id` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='role task relation';



# Dump of table yii2rbac_task_category
# ------------------------------------------------------------

DROP TABLE IF EXISTS `yii2rbac_task_category`;

CREATE TABLE `yii2rbac_task_category` (
  `task_category_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'category id',
  `task_category_name` varchar(50) NOT NULL COMMENT 'category name',
  PRIMARY KEY (`task_category_id`),
  UNIQUE KEY `task_category_name` (`task_category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='task category';



# Dump of table yii2rbac_task_item
# ------------------------------------------------------------

DROP TABLE IF EXISTS `yii2rbac_task_item`;

CREATE TABLE `yii2rbac_task_item` (
  `task_id` int(11) unsigned NOT NULL COMMENT 'task id',
  `item_id` int(11) unsigned NOT NULL COMMENT 'auth item id',
  UNIQUE KEY `unique_task_item` (`task_id`,`item_id`),
  KEY `idx_task_id` (`task_id`) USING BTREE,
  KEY `idx_item_id` (`item_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='task item relation';



# Dump of table yii2rbac_user_role
# ------------------------------------------------------------

DROP TABLE IF EXISTS `yii2rbac_user_role`;

CREATE TABLE `yii2rbac_user_role` (
  `user_id` int(10) unsigned NOT NULL COMMENT 'user id',
  `role_id` int(10) unsigned NOT NULL COMMENT 'role id',
  UNIQUE KEY `unique_user_role` (`user_id`,`role_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='user role relation';

# Dump of table yii2rbac_platform
# ------------------------------------------------------------

DROP TABLE IF EXISTS `yii2rbac_platform`;

CREATE TABLE `yii2rbac_platform` (
  `platform_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '平台ID',
  `platform_name` varchar(30) NOT NULL DEFAULT '' COMMENT '平台名称',
  PRIMARY KEY (`platform_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
