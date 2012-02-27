-- These are the core PowerDNS tables.

CREATE TABLE IF NOT EXISTS `domains` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `master` varchar(128) DEFAULT NULL,
  `last_check` int(11) DEFAULT NULL,
  `type` varchar(6) NOT NULL,
  `notified_serial` int(11) DEFAULT NULL,
  `account` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_index` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

CREATE TABLE IF NOT EXISTS `records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(6) NOT NULL,
  `content` varchar(4096) NOT NULL,
  `ttl` int(11) DEFAULT NULL,
  `prio` int(11) DEFAULT NULL,
  `change_date` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rec_name_index` (`name`),
  KEY `nametype_index` (`name`,`type`),
  KEY `domain_id` (`domain_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

CREATE TABLE IF NOT EXISTS `supermasters` (
  `ip` varchar(25) NOT NULL,
  `nameserver` varchar(255) NOT NULL,
  `account` varchar(40) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `records`
  ADD CONSTRAINT `records_ibfk_1` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- These tables contain templates

CREATE TABLE IF NOT EXISTS `zone_templ` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `descr` varchar(1024) NOT NULL,
  `owner` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

CREATE TABLE IF NOT EXISTS `zone_templ_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `zone_templ_id` int(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(6) NOT NULL,
  `content` varchar(255) NOT NULL,
  `ttl` int(11) DEFAULT NULL,
  `prio` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `zone_templ_id` (`zone_templ_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

ALTER TABLE `zone_templ_records`
  ADD CONSTRAINT `zone_templ_records_ibfk_1` FOREIGN KEY (`zone_templ_id`) REFERENCES `zone_templ` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- These tables contain users and their tokens

CREATE TABLE IF NOT EXISTS `tokens` (
  `token_hash` varchar(40) NOT NULL,
  `token_valid_until` int(11) NOT NULL,
  `token_user_id` int(11) NOT NULL,
  PRIMARY KEY (`token_hash`),
  KEY `token_user_id` (`token_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(16) NOT NULL,
  `password` varchar(34) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `description` varchar(1024) NOT NULL,
  `perm_templ` tinyint(4) NOT NULL DEFAULT '0',
  `active` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

ALTER TABLE `tokens`
  ADD CONSTRAINT `tokens_ibfk_1` FOREIGN KEY (`token_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
