<?php

use Civi\Utils\CivicallUtils;
use CRM_Civicall_ExtensionUtil as E;

class CRM_Civicall_Form_CivicallDashboard extends CRM_Civicall_Form_CivicallForm {

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

    parent::buildQuickForm();
  }


  public function postProcess(): void {
    $values = $this->exportValues();
    parent::postProcess();
  }

}
