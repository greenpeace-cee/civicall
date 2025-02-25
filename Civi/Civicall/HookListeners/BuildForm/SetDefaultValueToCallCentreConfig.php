<?php

namespace Civi\Civicall\HookListeners\BuildForm;

use Civi\Civicall\Utils\CivicallSettings;
use Civi\Core\Event\GenericHookEvent;
use CRM_Custom_Form_CustomDataByType;

class SetDefaultValueToCallCentreConfig {

  /**
   * @param GenericHookEvent $event
   */
  public static function run(GenericHookEvent $event) {
    if ($event->formName !== CRM_Custom_Form_CustomDataByType::class) {
      return;
    }

    $configurationCustomFieldId = CivicallSettings::getCallConfigurationCustomFieldId();

    if (empty($configurationCustomFieldId)) {
      return;
    }

    $configElementName = 'custom_' . $configurationCustomFieldId . '_-1';

    if (!$event->form->elementExists($configElementName)) {
      return;
    }

    $configElement = $event->form->getElement($configElementName);

    if ($configElement->getValue() !== CivicallSettings::CALL_CONFIG_DEFAULT_VALUE) {
      return;
    }

    $configElement->setValue(CivicallSettings::getExampleCallConfig());
  }

}
