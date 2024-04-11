<?php

require_once 'civicall.civix.php';

use Civi\Civicall\Utils\CallCenterConfiguration;
use Civi\Civicall\Utils\CivicallSettings;
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
    'permission' => 'administer CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
    'weight' => 70,
  ));
  _civicall_civix_navigationMenu($menu);
}

function civicall_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  if ($formName === CRM_Campaign_Form_Campaign::class) {
    $configurationCustomFieldId = CivicallSettings::getCallConfigurationCustomFieldId();

    if (!empty($configurationCustomFieldId)) {
      if ($form->getAction() === NULL) {// it is ADD action
        $fieldName = 'custom_' . $configurationCustomFieldId . '_-1';
      } elseif ($form->getAction() === CRM_Core_Action::UPDATE) {
        $fieldName = 'custom_' . $configurationCustomFieldId . '_' . $form->controller->get('entityId');
      }

      $configurationValue = NULL;
      if (!empty($fields[$fieldName])) {
        $configurationValue = $fields[$fieldName];
      }

      if (!is_null($configurationValue)) {
        $callCenterConfiguration = new CallCenterConfiguration($configurationValue);

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

function civicall_civicrm_buildForm($formName, $form) {
  if ($formName === CRM_Custom_Form_CustomDataByType::class) {
    // The length of default value of any custom fields is 255 chars.
    // Call config file has more than 255 chars.
    // That's why it added by hook.
    $configurationCustomFieldId = CivicallSettings::getCallConfigurationCustomFieldId();

    if (!empty($configurationCustomFieldId)) {
      $configElementName = 'custom_' . $configurationCustomFieldId . '_-1';

      if ($form->elementExists($configElementName)) {
        $configElement = $form->getElement($configElementName);
        if ($configElement->getValue() === CivicallSettings::CALL_CONFIG_DEFAULT_VALUE) {
          $callConfigPath = CRM_Civicall_ExtensionUtil::path('campaignConfigExample.json');

          if (file_exists($callConfigPath)) {
            $callConfig = file_get_contents($callConfigPath);
            $configElement->setValue($callConfig);
          }
        }
      }
    }
  }
}
