<?php

require_once 'civicall.civix.php';

use CRM_Civicall_ExtensionUtil as E;

/**
 * All hooks docs:
 * @link https://docs.civicrm.org/dev/en/latest/hooks/list/
 */

function civicall_civicrm_config(&$config): void {
  _civicall_civix_civicrm_config($config);

  // prevent add listeners twice
  if (isset(Civi::$statics[__FUNCTION__])) {
    return;
  }
  Civi::$statics[__FUNCTION__] = 1;

  Civi::dispatcher()->addListener(
    'hook_civicrm_links',
    'Civi\Civicall\HookListeners\Links\ApplyCallCenterLinks::run',
    PHP_INT_MAX - 1
  );
  Civi::dispatcher()->addListener(
    'hook_civicrm_validateForm',
    'Civi\Civicall\HookListeners\ValidateForm\ValidateCallCentreConfigs::run',
    PHP_INT_MAX - 1
  );
  Civi::dispatcher()->addListener(
    'hook_civicrm_buildForm',
    'Civi\Civicall\HookListeners\BuildForm\SetDefaultValueToCallCentreConfig::run',
    PHP_INT_MAX - 1
  );
}

function civicall_civicrm_install(): void {
  _civicall_civix_civicrm_install();
}

function civicall_civicrm_enable(): void {
  _civicall_civix_civicrm_enable();
}

function civicall_civicrm_navigationMenu(&$menu) {
  _civicall_civix_insert_navigation_menu($menu, 'Administer/System Settings', array(
    'label' => E::ts('Civicall'),
    'icon' => 'crm-i fa-phone',
    'name' => 'Civicall_Dashboard',
    'url' => 'civicrm/civicall/dashboard',
    'permission' => 'administer CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
    'weight' => 10,
  ));
  _civicall_civix_navigationMenu($menu);
}
