<?php

require_once 'civicall.civix.php';

use CRM_Civicall_ExtensionUtil as E;

/**
 * All hooks docs:
 * @link https://docs.civicrm.org/dev/en/latest/hooks/list/
 */

function civicall_civicrm_config(&$config): void {
  _civicall_civix_civicrm_config($config);
}

function civicall_civicrm_install(): void {
  _civicall_civix_civicrm_install();
}

function civicall_civicrm_enable(): void {
  _civicall_civix_civicrm_enable();
}

function civicall_civicrm_navigationMenu(&$menu) {
  _civicall_civix_insert_navigation_menu($menu, NULL, array(
    'label' => E::ts('Civicall Dashboard'),
    'icon' => 'crm-i fa-phone',
    'name' => 'Civicall_Dashboard',
    'url' => 'civicrm/civicall/dashboard',
    'permission' => 'access CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
    'weight' => 70,
  ));
  _civicall_civix_navigationMenu($menu);
}

function civicall_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  if ($formName === CRM_Campaign_Form_Campaign::class) {
    $configurationCustomField = \Civi\Api4\CustomField::get()
      ->addSelect( 'id', 'custom_group_id')
      ->addWhere('custom_group_id:name', '=', 'civicall_call_configuration')
      ->addWhere('name', '=', 'configuration')
      ->execute()
      ->first();

    if (!empty($configurationCustomField)) {
      $fieldNameEdit = 'custom_' . $configurationCustomField['id'] . '_1';
      $fieldNameCreate = 'custom_' . $configurationCustomField['id'] . '-1';
      $configurationValue = NULL;
      $fieldName = '';

      if (isset($fields[$fieldNameEdit])) {
        if (!empty($fields[$fieldNameEdit])) {
          $configurationValue = $fields[$fieldNameEdit];
        }
        $fieldName = $fieldNameEdit;
      }

      if (isset($fields[$fieldNameCreate])) {
        if (!empty($fields[$fieldNameCreate])) {
          $configurationValue = $fields[$fieldNameCreate];
        }
        $fieldName = $fieldNameCreate;
      }

      if (!is_null($configurationValue)) {
        $callCenterConfiguration = new \Civi\Utils\CallCenterConfiguration($configurationValue);

        if ($callCenterConfiguration->isHasErrors()) {
          $errors[$fieldName] = $callCenterConfiguration->getErrors();
        }

        if ($callCenterConfiguration->isHasWarnings()) {
          CRM_Core_Session::setStatus($callCenterConfiguration->getWarnings(), E::ts('Call center configuration warning'), 'warning');
        }
      } else {
        CRM_Core_Session::setStatus(E::ts('Empty configuration'), E::ts('Call center configuration warning'), 'warning');
      }
    }
  }
}
