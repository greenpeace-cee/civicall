<?php

require_once 'civicall.civix.php';

use CRM_Civicall_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function civicall_civicrm_config(&$config): void {
  _civicall_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function civicall_civicrm_install(): void {
  _civicall_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function civicall_civicrm_enable(): void {
  _civicall_civix_civicrm_enable();
}
