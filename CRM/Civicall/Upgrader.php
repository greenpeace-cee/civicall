<?php

use CRM_Civicall_ExtensionUtil as E;

class CRM_Civicall_Upgrader extends CRM_Extension_Upgrader_Base {

  public function install() {
    $this->validateConfiguration();
  }

  public function postInstall() {

  }

  /**
   * Validates configuration requires to use the extension
   */
  private function validateConfiguration() {
    $isCampaignComponentEnabled = in_array('CiviCampaign', Civi::settings()->get('enable_components'));

    if (!$isCampaignComponentEnabled) {
      $message = E::ts('Campaign component is disabled. To correctly work with extension please enable "Campaign" component. See "Administer->Configuration Checklist->Enable Components".');
      CRM_Core_Session::setStatus($message, 'Civical installation', 'warning');
    }
  }

}
