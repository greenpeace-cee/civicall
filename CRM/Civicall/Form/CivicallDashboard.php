<?php

use Civi\Civicall\Utils\CivicallUtils;
use CRM_Civicall_ExtensionUtil as E;

class CRM_Civicall_Form_CivicallDashboard extends CRM_Core_Form {

  public function buildQuickForm(): void {
    $params = [];
    $civicallActivities = CivicallUtils::getCivicallActivities($params);

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ],
    ]);

    $this->assign('civicallActivities', $civicallActivities);
    $this->assign('callCenterJsUrl', CRM_Civicall_ExtensionUtil::url('js/civicall.js'));

    parent::buildQuickForm();
  }


  public function postProcess(): void {
    $values = $this->exportValues();
    parent::postProcess();
  }

}
