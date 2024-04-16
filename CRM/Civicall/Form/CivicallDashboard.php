<?php

use Civi\Civicall\Utils\CallResponses;
use Civi\Civicall\Utils\CivicallSettings;
use Civi\Civicall\Utils\CivicallUtils;
use CRM_Civicall_ExtensionUtil as E;

class CRM_Civicall_Form_CivicallDashboard extends CRM_Core_Form {

  public $searchParams = [];

  public function preProcess() {
    $limit = CRM_Utils_Request::retrieve('limit', 'Integer', $this, false, 5);
    $targetContactId = CRM_Utils_Request::retrieve('target_contact_id', 'Integer', $this, false, null);
    $this->searchParams = ['limit' => $limit];
    if (!empty($targetContactId)) {
      $this->searchParams['target_contact_id'] = $targetContactId;
    }

    $civicallActivities = CivicallUtils::getCivicallActivities($this->searchParams);

    $this->assign('searchButtonName', '_qf_CivicallDashboard_submit');
    $this->assign('editCallResponsesLink', CRM_Utils_System::url('civicrm/admin/options', 'reset=1&gid=' . CallResponses::getCallResponsesOptionGroupId()));
    $this->assign('civicallActivities', $civicallActivities);
    $this->assign('callCenterJsUrl', CRM_Civicall_ExtensionUtil::url('js/civicall.js'));
  }

  public function buildQuickForm(): void {
    $this->add('number', 'activity_limit', 'Limit', ['min' => 1, 'class' => 'civicall__input civicall--width-150']);
    $this->add('textarea', 'available_responses', 'Available call responses:', ['cols' => 50, 'rows' => 6, 'class' => 'civicall__input civicall--textarea']);
    $this->add('textarea', 'call_config_example', 'Call config example:', ['cols' => 50, 'rows' => 6, 'class' => 'civicall__input civicall--textarea']);
    $this->addEntityRef('target_contact_id', 'Target contact', ['entity' => 'Contact', 'class' => 'civicall__input civicall--single-select civicall--width-150']);
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ],
    ]);

    parent::buildQuickForm();
  }


  public function postProcess(): void {
    $values = $this->exportValues();

    if (!empty($values['activity_limit'])) {
      $this->searchParams['limit'] = $values['activity_limit'];
    }

    if (isset($values['target_contact_id'])) {
      $this->searchParams['target_contact_id'] = $values['target_contact_id'];
    }

    $urlParams = 'reset=1';
    $urlParams .= '&limit=' . $this->searchParams['limit'];
    if (!empty($this->searchParams['target_contact_id'])) {
      $urlParams .= '&target_contact_id=' . $this->searchParams['target_contact_id'];
    }

    $session = CRM_Core_Session::singleton();
    $this->context = CRM_Utils_System::url('civicrm/civicall/dashboard', $urlParams);
    $session->pushUserContext($this->context);
    $this->controller->_destination = $this->context;

    parent::postProcess();
  }

  public function setDefaultValues() {

    return [
      'target_contact_id' =>  $this->searchParams['target_contact_id'] ?? null,
      'activity_limit' => $this->searchParams['limit'],
      'available_responses' => json_encode(array_values(CallResponses::getAvailableResponsesNames())),
      'call_config_example' => CivicallSettings::getExampleCallConfig(),
    ];
  }

}
