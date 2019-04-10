/*
SQLyog Enterprise v12.09 (64 bit)
MySQL - 5.5.46-log : Database - test_ccoin
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`test_ccoin` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `test_ccoin`;

/*Table structure for table `auth_rule` */

DROP TABLE IF EXISTS `auth_rule`;

CREATE TABLE `auth_rule` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(80) NOT NULL COMMENT '规则唯一标识',
  `title` char(20) NOT NULL COMMENT '规则中文名称',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态：1为验证condition附加条件',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '规则状态：1为正常，0为禁用',
  `condition` char(100) NOT NULL DEFAULT '' COMMENT '附加条件：不为空时，满足附加条件,规则才为有效的规则；为空规则存在就验证',
  `category` char(10) NOT NULL DEFAULT '' COMMENT '权限类别(增添字段)',
  `class` char(20) NOT NULL DEFAULT '' COMMENT '权限分组(增添字段)',
  `pid` int(4) NOT NULL DEFAULT '0' COMMENT '作为菜单时的父级id',
  `menu_title` char(20) NOT NULL DEFAULT '' COMMENT '用于作为菜单的中文名称(扩展)',
  `icon` varchar(64) NOT NULL DEFAULT '' COMMENT '图标',
  `order` int(11) NOT NULL DEFAULT '0' COMMENT '菜单顺序(扩展)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=407 DEFAULT CHARSET=utf8;

/*Data for the table `auth_rule` */

insert  into `auth_rule`(`id`,`name`,`title`,`type`,`status`,`condition`,`category`,`class`,`pid`,`menu_title`,`icon`,`order`) values (1,'1','交易管理',1,1,'','title','Class',1,'交易管理','fa-institution',1),(2,'2','用户管理',1,1,'','title','User',2,'用户管理','fa-user',2),(3,'3','通知管理',1,1,'','title','Notice',3,'通知管理','fa-envelope',3),(4,'4','系统管理',1,1,'','title','Sys',4,'系统管理','fa-check-square-o',4),(5,'editInfo.html','编辑资料',1,1,'','page','Sys',4,'','',5),(6,'profile.html','修改密码',1,1,'','page','Sys',4,'','',5),(355,'auths.html','角色管理',1,1,'','page','Sys',4,'角色管理','',1),(356,'tradeConfig.html','系统配置',1,1,'','page','Sys',4,'系统配置','',2),(357,'authsAction.html','角色详情',1,1,'','page','Sys',4,'','',3),(358,'noticeDetail.html','通知详情',1,1,'','page','Notice',3,'','',3),(359,'user.html','用户列表',1,1,'','page','User',2,'用户列表','',1),(360,'userNexus.html','用户关系列表',1,1,'','page','User',2,'用户关系列表','',2),(361,'dishonest.html','异常订单列表',1,1,'','page','Class',1,'异常订单列表','',3),(362,'accounts.html','管理员列表',1,1,'','page','User',2,'管理员列表','',3),(363,'accountAction.html','管理员详情',1,1,'','page','User',2,'','',5),(364,'trade.html','交易大厅',1,1,'','page','Class',1,'交易大厅','',1),(365,'order.html','订单管理',1,1,'','page','Class',1,'订单管理','',2),(366,'sale.html','账户明细',1,1,'','page','User',2,'账户明细','',3),(367,'chart.html','系数图表',1,1,'','page','Class',1,'系数图表','',4),(368,'orderDetail.html','订单详情',1,1,'','page','Class',1,'','',10),(385,'notice.html','通知列表',1,1,'','page','Notice',3,'通知列表','',3),(386,'userAction.html','用户详情',1,1,'','page','User',2,'','',1),(401,'fictitious.html','虚拟号管理',1,1,'','page','User',2,'虚拟号管理','',5),(402,'fictitiousDetail.html','虚拟号详情',1,1,'','page','User',2,'','',6);

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
