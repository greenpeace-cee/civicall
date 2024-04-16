<?php

namespace Civi\Civicall\HookListeners\ValidateForm;

use CRM_Civicall_ExtensionUtil as E;
use Civi\Civicall\Utils\CallCenterConfiguration;
use Civi\Civicall\Utils\CivicallSettings;
use Civi\Core\Event\GenericHookEvent;
use CRM_Campaign_Form_Campaign;
use CRM_Core_Action;
use CRM_Core_Session;

class ValidateCallCentreConfigs {

  /**
   * @param GenericHookEvent $event
   */
  public static function run(GenericHookEvent $event) {
    if ($event->formName !== CRM_Campaign_Form_Campaign::class) {
      return;
    }

    $configurationCustomFieldId = CivicallSettings::getCallConfigurationCustomFieldId();

    if (empty($configurationCustomFieldId)) {
      return;
    }

    if ($event->form->getAction() === NULL) {// NULL is ADD action
      $fieldName = 'custom_' . $configurationCustomFieldId . '_-1';
    } elseif ($event->form->getAction() === CRM_Core_Action::UPDATE) {
      $fieldName = 'custom_' . $configurationCustomFieldId . '_' . $event->form->controller->get('entityId');
    }

    $configurationValue = NULL;
    if (!empty($event->fields[$fieldName])) {
      $configurationValue = $event->fields[$fieldName];
    }

    if (is_null($configurationValue)) {
      CRM_Core_Session::setStatus(E::ts('Empty configuration'), E::ts('Call center configuration warning'), 'warning');
      return;
    }

    $callCenterConfiguration = new CallCenterConfiguration($configurationValue);

    if ($callCenterConfiguration->isHasErrors()) {
      $event->errors[$fieldName] = $callCenterConfiguration->getErrors();
    }

    if ($callCenterConfiguration->isHasWarnings()) {
      CRM_Core_Session::setStatus($callCenterConfiguration->getWarnings(), E::ts('Call center configuration warning'), 'warning');
    }
  }

}
