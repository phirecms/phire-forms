--
-- Forms Module MySQL Database for Phire CMS 2.0
--

-- --------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------

--
-- Table structure for table `forms`
--

CREATE TABLE IF NOT EXISTS `[{prefix}]forms` (
  `id` int(16) NOT NULL AUTO_INCREMENT,
  `method` varchar(255),
  `name` varchar(255),
  `to` varchar(255),
  `from` varchar(255),
  `reply_to` varchar(255),
  `action` varchar(255),
  `redirect` varchar(255),
  `attributes` varchar(255),
  `submit_value` varchar(255),
  `submit_attributes` varchar(255),
  `captcha` int(1),
  `csrf` int(1),
  `force_ssl` int(1),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=14001 ;

-- --------------------------------------------------------

--
-- Table structure for table `form_submissions`
--

CREATE TABLE IF NOT EXISTS `[{prefix}]form_submissions` (
  `id` int(16) NOT NULL AUTO_INCREMENT,
  `form_id` int(16) NOT NULL,
  `timestamp` datetime,
  `ip_address` varchar(255),
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_form_id` FOREIGN KEY (`form_id`) REFERENCES `[{prefix}]forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=15001 ;

-- --------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 1;
