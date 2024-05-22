<?php

use Civi\Api4\Activity;
use Civi\Api4\CallLog;
use Civi\Civicall\Utils\CallCenterActions;
use Civi\Civicall\Utils\CallCenterConfiguration;
use Civi\Civicall\Utils\CallLogsUtils;
use Civi\Civicall\Utils\CallResponses;
use Civi\Civicall\Utils\CivicallSettings;
use Civi\Civicall\Utils\CivicallUtils;
use CRM_Civicall_ExtensionUtil as E;

class CRM_Civicall_Form_CivicallCallCenter extends CRM_Core_Form {

  public $activity = [];
  public $targetContact = [];
  public $targetCampaign = [];
  public $callLogsCount = 0;
  public $isFormInPopup = false;
  public $isCallAlreadyClosed = false;

  /**
   * @var CallCenterConfiguration
   */
  public $callCenterConfiguration = [];

  public function preProcess() {
    CRM_Utils_System::setTitle(E::ts('Call Center'));

    $isJsonSnippet = CRM_Utils_Request::retrieve('snippet', 'String', $this) === 'json';
    if ($isJsonSnippet) {
      $this->isFormInPopup = true;
    }

    $activityId = CRM_Utils_Request::retrieve('activity_id', 'Integer', $this);
    if (empty($activityId)) {
      $this->showError('Cannot find activity id.');
    }

    $this->activity = CivicallUtils::getActivity($activityId);
    if (empty($this->activity)) {
      $this->showError('Cannot find activity.');
    }

    if ($this->activity['isCallCenterEnabled'] !== true) {
      $this->showError('CallCenter not enabled for target campaign');
    }

    $this->targetContact = CivicallUtils::getCallCenterTargetContact($activityId);
    if (empty($this->targetContact)) {
      $this->showError('Cannot find contact.');
    }

    $this->targetCampaign = CivicallUtils::getCallCenterTargetCampaign($activityId);
    if (empty($this->targetCampaign)) {
      $this->showError('Cannot find campaign.');
    }

    $this->callCenterConfiguration = new CallCenterConfiguration($this->activity['campaignConfiguration']);
    if ($this->callCenterConfiguration->isHasErrors()) {
      $this->showError('CallCenter Configuration error:' . $this->callCenterConfiguration->getErrors());
    }

    $this->isCallAlreadyClosed = CivicallUtils::isCallAlreadyClosed($this->activity['id']);
    $this->callLogsCount = CallLogsUtils::getActivityCallLogsCount($this->activity['id']);
    $this->callCenterConfiguration->loadAfformModules([
      'contact_id' => $this->targetContact['id'],
      'activity_id' => $this->targetContact['id'],
      'campaign_id' => $this->targetCampaign['id'],
    ]);

    $this->assign('responseLimitMessage', CivicallSettings::getResponseLimitMessage($this->callCenterConfiguration->getResponseLimit(), $this->callLogsCount));
    $this->assign('pageLoaderConfiguration', $this->callCenterConfiguration->getPageLoader());
    $this->assign('isShowTimer', $this->callCenterConfiguration->getIsShowTimer());
    $this->assign('callLogs', CallLogsUtils::getCallCenterLogs($activityId));
    $this->assign('activity', $this->activity);
    $this->assign('targetContact', $this->targetContact);
    $this->assign('targetCampaign', $this->targetCampaign);
    $this->assign('isFormInPopup', $this->isFormInPopup);
    $this->assign('isCallAlreadyClosed', $this->isCallAlreadyClosed);
    $this->assign('callCenterJsUrl', CRM_Civicall_ExtensionUtil::url('js/civicall.js?v=1'));
    $this->assign('callCenterNotesWrapId', 'callCenterNotesWrapId_' . $this->activity['id'] . '_' . time());
    $this->assign('alreadyClosedCallMessage', CivicallSettings::getAlreadyClosedCallMessage());

    $this->assign('rescheduleCallButtonName', CallCenterActions::getButtonName(CallCenterActions::RESCHEDULE_CALL));
    $this->assign('closeCallButtonName', CallCenterActions::getButtonName(CallCenterActions::CLOSE_CALL));
    $this->assign('reopenCallButtonName', CallCenterActions::getButtonName(CallCenterActions::REOPEN_CALL));
    $this->assign('updateCallResponseButtonName', CallCenterActions::getButtonName(CallCenterActions::UPDATE_CALL_RESPONSE));
    $this->assign('cancelButtonName', '_qf_CivicallCallCenter_cancel');

    parent::preProcess();
  }

  public function buildQuickForm(): void {
    $preliminaryResponseOptions = $this->callCenterConfiguration->getPreliminaryResponseOptions();
    $finalResponseOptions = $this->callCenterConfiguration->getAvailableResponseOptions();

    $this->add('textarea', 'notes', ts('Notes'), ['cols' => 50, 'rows' => 6, 'class' => 'civicall__input civicall--textarea civicall--width-100-percent']);
    $this->add('datepicker', 'scheduled_call_date', 'Scheduled Call Date', ['class' => 'civicall__input civicall--datepicker'], FALSE, ['minDate' => date('Y-m-d')]);
    $this->add('datepicker', 'response_call_date', 'Response Date', ['class' => 'civicall__input civicall--datepicker'], FALSE, ['minDate' => date('Y-m-d')]);
    $this->add('text', 'start_call_time_timestamp', '');
    $this->add('text', 'activity_id', '');
    $this->add('datepicker', 'reopen_scheduled_call_date', 'Reopen Schedule Date', ['class' => 'civicall__input civicall--datepicker'], FALSE, ['minDate' => date('Y-m-d')]);

    $this->add('select2', 'new_final_call_response', 'New final response', $finalResponseOptions, FALSE, [
      'class' => 'civicall__input civicall--single-select',
      'placeholder' => ts('- select response -')
    ]);
    $this->add('select2', 'preliminary_call_response', 'Preliminary response', $preliminaryResponseOptions, FALSE, [
      'class' => 'civicall__input civicall--single-select',
      'placeholder' => ts('- select response -')
    ]);
    $this->add('select2', 'final_call_response', 'Final response', $finalResponseOptions, FALSE, [
      'class' => 'civicall__input civicall--single-select',
      'placeholder' => ts('- select response -')
    ]);

    $this->addButtons([
      [
        'type' => 'cancel',
        'name' => E::ts('Cancel without saving'),
        'isDefault' => false,
      ],
      [
        'type' => 'submit',
        'name' => E::ts('Civicall Call Center Actions'),
        'isDefault' => false,
      ],
    ]);

    parent::buildQuickForm();
  }

  public function postProcess(): void {
    $values = $this->exportValues();

    if (CallCenterActions::isAction($values, CallCenterActions::RESCHEDULE_CALL) && !$this->isCallAlreadyClosed) {
      $this->runRescheduleCallAction($values);
    } elseif (CallCenterActions::isAction($values,CallCenterActions::CLOSE_CALL) && !$this->isCallAlreadyClosed) {
      $this->runCloseCallAction($values);
    } elseif (CallCenterActions::isAction($values,CallCenterActions::REOPEN_CALL) && $this->isCallAlreadyClosed) {
      $this->runReopenCallAction($values);
    } elseif (CallCenterActions::isAction($values,CallCenterActions::UPDATE_CALL_RESPONSE) && $this->isCallAlreadyClosed) {
      $this->runUpdateCallResponseAction($values);
    } else {
      throw new Exception('Unexpected CallCenter action.');
    }

    $this->fixFormRedirection();
    parent::postProcess();
  }

  private function runReopenCallAction($values) {
    $callLogsCount = CallLogsUtils::getActivityCallLogsCount($this->activity['id']);
    $responseActivityIds = CivicallUtils::getRelatedResponseActivities($this->activity['id']);

    foreach ($responseActivityIds as $responseActivityId) {
      Activity::delete()->addWhere('id', '=', $responseActivityId)->execute();
    }

    Activity::update()
      ->addWhere('id', '=', $this->activity['id'])
      ->addValue('status_id:name', 'Scheduled')
      ->addValue('civicall_call_details.final_response_date', null)
      ->addValue('civicall_call_details.civicall_call_final_response', null)
      ->addValue('details', $values['notes'])
      ->addValue('activity_date_time', $values['reopen_scheduled_call_date'])
      ->addValue('civicall_call_details.civicall_response_counter', $callLogsCount)
      ->addValue('civicall_call_details.civicall_schedule_date', $values['reopen_scheduled_call_date'])
      ->execute();
  }

  private function runUpdateCallResponseAction($values) {
    $callLogId = CivicallUtils::getLastCallLogId($values['activity_id']);

    CallLog::update()
      ->addWhere('id', '=', $callLogId)
      ->addValue('call_response_id', $values['new_final_call_response'])
      ->execute();

    Activity::update()
      ->addWhere('id', '=', $this->activity['id'])
      ->addValue('status_id:name', 'Scheduled')
      ->addValue('civicall_call_details.civicall_call_final_response', $values['new_final_call_response'])
      ->addValue('details', $values['notes'])
      ->execute();
  }

  private function runRescheduleCallAction($values) {
    $startCallDate = CivicallUtils::convertTimestampToDateTimeObject(($values['start_call_time_timestamp'] ?? null));

    CallLog::create()
      ->addValue('activity_id', $values['activity_id'])
      ->addValue('call_start_date', $startCallDate->format('Y-m-d H:i:s'))
      ->addValue('call_end_date', (new DateTime)->format('Y-m-d H:i:s'))
      ->addValue('created_id', CRM_Core_Session::getLoggedInContactID())
      ->addValue('call_response_id', $values['preliminary_call_response'])
      ->execute();

    $callLogsCount = CallLogsUtils::getActivityCallLogsCount($this->activity['id']);

    Activity::update()
      ->addWhere('id', '=', $this->activity['id'])
      ->addValue('activity_date_time', $values['scheduled_call_date'])
      ->addValue('status_id:name', 'Scheduled')
      ->addValue('civicall_call_details.civicall_schedule_date', $values['scheduled_call_date'])
      ->addValue('details', $values['notes'])
      ->addValue('civicall_call_details.civicall_response_counter', $callLogsCount)
      ->execute();

    CRM_Core_Session::setStatus(E::ts("Call is rescheduled!"), E::ts('Success'), 'success');
  }

  private function runCloseCallAction($values) {
    $startCallDate = CivicallUtils::convertTimestampToDateTimeObject(($values['start_call_time_timestamp'] ?? null));

    CallLog::create()
      ->addValue('activity_id', $values['activity_id'])
      ->addValue('call_start_date', $startCallDate->format('Y-m-d H:i:s'))
      ->addValue('call_end_date', (new DateTime)->format('Y-m-d H:i:s'))
      ->addValue('created_id', CRM_Core_Session::getLoggedInContactID())
      ->addValue('call_response_id', $values['final_call_response'])
      ->execute();

    $callLogsCount = CallLogsUtils::getActivityCallLogsCount($this->activity['id']);

    Activity::update()
      ->addWhere('id', '=', $this->activity['id'])
      ->addValue('status_id:name', 'Completed')
      ->addValue('civicall_call_details.final_response_date', $values['response_call_date'])
      ->addValue('civicall_call_details.civicall_call_final_response', $values['final_call_response'])
      ->addValue('details', $values['notes'])
      ->addValue('civicall_call_details.civicall_response_counter', $callLogsCount)
      ->execute();

    $finalCallResponse = CallResponses::getResponseByValue($values['final_call_response']);

    $responseActivity = Activity::create()
      ->addValue('activity_type_id:name', CivicallSettings::RESPONSE_CALL_ACTIVITY_TYPE)
      ->addValue('activity_type_id:description', 'Response Outgoing Call, activity_id=' . $this->activity['id'])
      ->addValue('subject', $finalCallResponse['label'])
      ->addValue('activity_date_time', $values['response_call_date'])
      ->addValue('campaign_id', $this->targetCampaign['id'])
      ->addValue('source_contact_id', CRM_Core_Session::getLoggedInContactID())
      ->addValue('target_contact_id', $this->targetContact['id'])
      ->execute()
      ->first();

    CivicallUtils::linkActivity($responseActivity['id'], $this->activity['id']);

    CRM_Core_Session::setStatus(E::ts("Closed call and saved!"), E::ts('Success'), 'success');
  }

  public function addRules() {
    $this->addFormRule([self::class, 'validateForm']);
  }

  public static function validateForm($values) {
    $errors = [];

    if (CallCenterActions::isAction($values, CallCenterActions::RESCHEDULE_CALL)) {
      if (empty($values['scheduled_call_date'])) {
        $errors['scheduled_call_date'] = "Scheduled Call Date is required field!";
      }
      if (empty($values['preliminary_call_response'])) {
        $errors['preliminary_call_response'] = "Preliminary response is required field!";
      }
    }

    if (CallCenterActions::isAction($values, CallCenterActions::CLOSE_CALL)) {
      if (empty($values['response_call_date'])) {
        $errors['response_call_date'] = "Response Date is required field!";
      }
      if (empty($values['final_call_response'])) {
        $errors['final_call_response'] = "Final response response is required field!";
      }
    }

    if (CallCenterActions::isAction($values, CallCenterActions::REOPEN_CALL)) {
      if (empty($values['reopen_scheduled_call_date'])) {
        $errors['reopen_scheduled_call_date'] = "Reopen scheduled Date is required field!";
      }
    }

    if (CallCenterActions::isAction($values, CallCenterActions::UPDATE_CALL_RESPONSE)) {
      if (empty($values['new_final_call_response'])) {
        $errors['new_final_call_response'] = "New final response response is required field!";
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  public function setDefaultValues() {
    $defaults = [];

    $defaults['notes'] = $this->activity['details'];
    $defaults['response_call_date'] = (new DateTime())->format('Y-m-d H:i:s');
    $defaults['reopen_scheduled_call_date'] = (new DateTime())->format('Y-m-d H:i:s');
    $defaults['start_call_time_timestamp'] = (new DateTime())->getTimestamp();
    $defaults['activity_id'] = $this->activity['id'];

    $scheduleOffsets = $this->callCenterConfiguration->getScheduleOffsets();
    if (!empty($scheduleOffsets[$this->callLogsCount]['calculatedDate'])) {
      $defaults['scheduled_call_date'] = $scheduleOffsets[$this->callLogsCount]['calculatedDate'];
    } else {
      $defaults['scheduled_call_date'] = (new DateTime())->format('Y-m-d H:i:s');
    }

    if (!empty($this->callCenterConfiguration->getPreliminaryResponseDefaultResponseName())) {
      $defaults['preliminary_call_response'] = CallResponses::getResponseValueByName($this->callCenterConfiguration->getPreliminaryResponseDefaultResponseName());
    }

    if (!empty($this->callCenterConfiguration->getFinalResponseDefaultResponseName())) {
      $defaults['final_call_response'] = CallResponses::getResponseValueByName($this->callCenterConfiguration->getFinalResponseDefaultResponseName());
    }

    return $defaults;
  }

  public function showError($message) {
    // To show error message redirect to error page. Exception messages doesn't show at popups.
    if ($this->isFormInPopup) {
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/civicall/error', 'reset=1&error-message=' . urlencode($message)));
    }

    throw new Exception($message);
  }

  private function fixFormRedirection() {
    $session = CRM_Core_Session::singleton();
    $this->context = CRM_Utils_System::url('civicrm/civicall/call-center', "reset=1&activity_id={$this->activity['id']}");
    $session->pushUserContext($this->context);
    $this->controller->_destination = $this->context;
  }

}
