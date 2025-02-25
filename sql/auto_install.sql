-- +--------------------------------------------------------------------+
-- | Copyright CiviCRM LLC. All rights reserved.                        |
-- |                                                                    |
-- | This work is published under the GNU AGPLv3 license with some      |
-- | permitted exceptions and without any warranty. For full license    |
-- | and copyright information, see https://civicrm.org/licensing       |
-- +--------------------------------------------------------------------+
--
-- Generated from schema.tpl
-- DO NOT EDIT.  Generated by CRM_Core_CodeGen
--
-- /*******************************************************
-- *
-- * Clean up the existing tables - this section generated from drop.tpl
-- *
-- *******************************************************/

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `civicrm_call_log`;

SET FOREIGN_KEY_CHECKS=1;
-- /*******************************************************
-- *
-- * Create new tables
-- *
-- *******************************************************/

-- /*******************************************************
-- *
-- * civicrm_call_log
-- *
-- * Call log
-- *
-- *******************************************************/
CREATE TABLE `civicrm_call_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique CallLog ID',
  `activity_id` int unsigned NOT NULL COMMENT 'FK to Activity',
  `call_start_date` datetime NOT NULL COMMENT 'Call start date',
  `call_end_date` datetime NOT NULL COMMENT 'Call end date',
  `created_id` int unsigned COMMENT 'Created by contact, FK to Contact',
  `call_response_id` int unsigned NOT NULL COMMENT 'Call response, FK to OptionValue',
  PRIMARY KEY (`id`),
  CONSTRAINT FK_civicrm_call_log_activity_id FOREIGN KEY (`activity_id`) REFERENCES `civicrm_activity`(`id`) ON DELETE CASCADE,
  CONSTRAINT FK_civicrm_call_log_created_id FOREIGN KEY (`created_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE SET NULL
)
ENGINE=InnoDB;
