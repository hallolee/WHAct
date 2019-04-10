
CREATE DATABASE /*!32312 IF NOT EXISTS*/`test_common` /*!40100 DEFAULT CHARACTER SET latin1 */;

/*Table structure for table `auth_group` */

DROP TABLE IF EXISTS `auth_group`;

CREATE TABLE `auth_group` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `title` char(100) NOT NULL DEFAULT '' COMMENT '角色组名',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1为正常，0为禁用',
  `rules` text NOT NULL COMMENT '详细规则',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `auth_group` */

insert  into `auth_group`(`id`,`title`,`status`,`rules`) values (1,'管理员',1,'0');

/*Table structure for table `auth_group_access` */

DROP TABLE IF EXISTS `auth_group_access`;

CREATE TABLE `auth_group_access` (
  `uid` mediumint(8) unsigned NOT NULL COMMENT '用户id',
  `group_id` mediumint(8) unsigned NOT NULL COMMENT '角色组id',
  UNIQUE KEY `uid_group_id` (`uid`,`group_id`) USING BTREE,
  KEY `uid` (`uid`) USING BTREE,
  KEY `group_id` (`group_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Data for the table `auth_group_access` */

insert  into `auth_group_access`(`uid`,`group_id`) values (1,1);

/*Table structure for table `auth_rule` */

DROP TABLE IF EXISTS `auth_rule`;

CREATE TABLE `auth_rule` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT COMMENT '规则ID',
  `name` char(80) NOT NULL COMMENT '规则唯一标识',
  `title` char(20) NOT NULL COMMENT '规则中文名称',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态：1为验证condition附加条件',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '规则状态：1为正常，0为禁用',
  `condition` char(100) NOT NULL DEFAULT '' COMMENT '附加条件：不为空时，满足附加条件,规则才为有效的规则；为空规则存在就验证',
  `category` char(10) NOT NULL DEFAULT '' COMMENT '权限类别(扩展)',
  `class` char(20) NOT NULL DEFAULT '' COMMENT '权限分组(扩展)',
  `pid` int(4) NOT NULL DEFAULT '0' COMMENT '作为菜单时的父级id(扩展)',
  `menu_title` char(20) NOT NULL DEFAULT '' COMMENT '用于作为菜单的中文名称(扩展)',
  `icon` varchar(64) NOT NULL DEFAULT '' COMMENT '图标(扩展)',
  `order` int(11) NOT NULL DEFAULT '0' COMMENT '菜单顺序(扩展)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='规则明细信息表';

/*Data for the table `auth_rule` */

/*Table structure for table `basic_info` */

DROP TABLE IF EXISTS `basic_info`;

CREATE TABLE `basic_info` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '内部ID',
  `field` varchar(256) DEFAULT NULL COMMENT '字段名',
  `value` varchar(128) DEFAULT NULL COMMENT '字段值',
  `module` varchar(256) DEFAULT NULL COMMENT '所属模块（作为分类标记）',
  `rem` varchar(256) DEFAULT NULL COMMENT '字段备注',
  `mtime` int(11) DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='系统基本信息';

/*Data for the table `basic_info` */

/*Table structure for table `refer` */

DROP TABLE IF EXISTS `refer`;

CREATE TABLE `refer` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '内部ID',
  `uid` int(11) NOT NULL COMMENT '用户id',
  `upline` int(11) NOT NULL DEFAULT '0' COMMENT '介绍人id',
  `atime` int(11) NOT NULL COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='推荐关系表';

/*Data for the table `refer` */

/*Table structure for table `user_backend` */

DROP TABLE IF EXISTS `user_backend`;

CREATE TABLE `user_backend` (
  `uid` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '内部ID',
  `user` varchar(128) NOT NULL COMMENT '账号',
  `pass` varchar(128) NOT NULL COMMENT '密码',
  `name` varchar(256) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `sex` tinyint(4) NOT NULL DEFAULT '0' COMMENT '性别（0=男，1=女）',
  `icon` varchar(256) NOT NULL DEFAULT '' COMMENT '头像路径',
  `idcard` varchar(128) NOT NULL DEFAULT '' COMMENT '身份证号码',
  `phone` char(15) NOT NULL DEFAULT '' COMMENT '联系电话',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0=正常，1=禁用',
  `atime` int(11) NOT NULL COMMENT '添加时间',
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='管理员表';

/*Data for the table `user_backend` */

insert  into `user_backend`(`uid`,`user`,`pass`,`name`,`sex`,`icon`,`idcard`,`phone`,`status`,`atime`) values (1,'admin','14e1b600b1fd579f47433b88e8d85291','',0,'','','',0,1500880951);

/*Table structure for table `user_client` */

DROP TABLE IF EXISTS `user_client`;

CREATE TABLE `user_client` (
  `uid` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `user` varchar(128) NOT NULL DEFAULT '' COMMENT '账号',
  `pass` varchar(128) NOT NULL DEFAULT '' COMMENT '密码',
  `openid` varchar(64) NOT NULL DEFAULT '' COMMENT '微信openid',
  `nickname` varchar(256) NOT NULL DEFAULT '' COMMENT '昵称',
  `icon` varchar(256) NOT NULL DEFAULT '' COMMENT '用户头像',
  `sex` tinyint(4) NOT NULL DEFAULT '0' COMMENT '性别（0=男，1=女）',
  `birthday` varchar(12) NOT NULL DEFAULT '' COMMENT '出生日期',
  `phone` char(15) NOT NULL DEFAULT '' COMMENT '电话',
  `invitecode` varchar(128) NOT NULL DEFAULT '' COMMENT '邀请码',
  `intro` varchar(255) NOT NULL DEFAULT '' COMMENT '个人简介',
  `checkin_last_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后一次签到时间',
  `checkin_count` int(11) NOT NULL DEFAULT '0' COMMENT '连续签到次数',
  `atime` int(11) NOT NULL COMMENT '注册时间',
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户表';

/*Data for the table `user_client` */