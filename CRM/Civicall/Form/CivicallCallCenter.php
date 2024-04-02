<?php

use Civi\Api4\Activity;
use Civi\Api4\CallLogs;
use Civi\Api4\OptionValue;
use Civi\Utils\CallCenterConfiguration;
use Civi\Utils\CallResponses;
use Civi\Utils\CivicallSettings;
use Civi\Utils\CivicallUtils;
use CRM_Civicall_ExtensionUtil as E;

class CRM_Civicall_Form_CivicallCallCenter extends CRM_Civicall_Form_CivicallForm {

  public $activity = [];
  public $callLogsCount = 0;
  public $isFormInPopup = false;

  /**
   * @var CallCenterConfiguration
   */
  public $callCenterConfiguration = [];

  public function preProcess() {
    CRM_Utils_System::setTitle(E::ts('Call Center'));

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

    $targetContact = CivicallUtils::getCallCenterTargetContact($activityId);
    if (empty($targetContact)) {
      $this->showError('Cannot find contact.');
    }

    $targetCampaign = CivicallUtils::getCallCenterTargetCampaign($activityId);
    if (empty($targetCampaign)) {
      $this->showError('Cannot find campaign.');
    }

    $isJsonSnippet = CRM_Utils_Request::retrieve('snippet', 'String', $this) === 'json';
    if ($isJsonSnippet) {
      $this->isFormInPopup = true;
    }

    $this->callCenterConfiguration = new CallCenterConfiguration($this->activity['campaignConfiguration']);
    if ($this->callCenterConfiguration->isHasErrors()) {
      throw new Exception('CallCenter Configuration error:' . $this->callCenterConfiguration->getErrors());
    }

    $this->callLogsCount = CivicallUtils::getActivityCallLogsCount($this->activity['id']);

    $this->assign('responseLimitMessage', CivicallSettings::getResponseLimitMessage($this->callCenterConfiguration->getResponseLimit(), $this->callLogsCount));
    $this->assign('pageLoaderConfiguration', $this->callCenterConfiguration->getPageLoader());
    $this->assign('isShowTimer', $this->callCenterConfiguration->getIsShowTimer());
    $this->assign('callLogs', CivicallUtils::getCallCenterLogs($activityId));
    $this->assign('activity', $this->activity);
    $this->assign('targetContact', $targetContact);
    $this->assign('targetCampaign', $targetCampaign);
    $this->assign('rescheduleButtonName', self::getRescheduleButtonName());
    $this->assign('closeAndSaveButtonName', self::getCloseAndSaveButtonName());
    $this->assign('closeAndWithoutSaveButtonName', self::getCloseAndWithoutSaveButtonName());
    $this->assign('isFormInPopup', $this->isFormInPopup);
    parent::preProcess();
  }

  public function buildQuickForm(): void {
    $preliminaryResponseOptions = $this->callCenterConfiguration->getPreliminaryResponseOptions();
    $finalResponseOptions = $this->callCenterConfiguration->getAvailableResponseOptions();

    $this->add('textarea', 'notes', ts('Notes'), ['cols' => 50, 'rows' => 6, 'class' => 'civicall__input civicall--textarea civicall--width-100-percent']);
    $this->add('datepicker', 'scheduled_call_date', 'Scheduled Call Date', ['class' => 'civicall__input civicall--datepicker'], FALSE, ['minDate' => date('Y-m-d')]);
    $this->add('datepicker', 'response_call_date', 'Response Date', ['class' => 'civicall__input civicall--datepicker'], FALSE, ['minDate' => date('Y-m-d')]);
    $this->add('select', 'preliminary_call_response', 'Preliminary response', $preliminaryResponseOptions, FALSE, ['class' => 'civicall__input civicall--width-150']);
    $this->add('select', 'final_call_response', 'Final response', $finalResponseOptions, FALSE, ['class' => 'civicall__input civicall--width-150']);
    $this->add('hidden', 'start_call_time_timestamp', (new DateTime())->getTimestamp());
    $this->add('hidden', 'activity_id', $this->activity['id']);

    $this->addButtons([
      [
        'type' => 'cancel',
        'name' => E::ts('Close call without save'),
        'isDefault' => false,
      ],
      [
        'type' => 'submit',
        'name' => E::ts('Close call and save'),
        'isDefault' => false,
      ],
      [
        'type' => 'next',
        'name' => E::ts('Reschedule call'),
        'isDefault' => false,
      ],
    ]);

    parent::buildQuickForm();
  }

  public function postProcess(): void {
    $values = $this->exportValues();
    try {
      $startCallDate = DateTime::createFromFormat('U', $values['start_call_time_timestamp']);
    } catch (Exception $e) {
      throw new Exception('Wrong start call date');
    }
    if (!($startCallDate instanceof DateTime)) {
      throw new Exception('Wrong start call date');
    }

    $isRescheduleAction = isset($values[self::getRescheduleButtonName()]);
    $isCloseAndSaveAction = isset($values[self::getCloseAndSaveButtonName()]);
    $currentTimeZone = (new DateTime)->getTimezone()->getName();
    $startCallDate->setTimeZone(new DateTimeZone($currentTimeZone));

    if ($isCloseAndSaveAction) {
      $optionValue = OptionValue::get()
        ->addSelect('value')
        ->addWhere('id', '=', $values['final_call_response'])
        ->execute()
        ->first();
      $finalCallResponseValue = $optionValue['value'];

      Activity::update()
        ->addWhere('id', '=', $this->activity['id'])
        ->addValue('status_id:name', 'Completed')
        ->addValue('civicall_call_details.final_response_date', $values['response_call_date'])
        ->addValue('civicall_call_details.civicall_call_final_response', $finalCallResponseValue)
        ->execute();

      CRM_Core_Session::setStatus(E::ts("Closed call and saved!"), E::ts('Success'), 'success');
    } elseif ($isRescheduleAction) {
      Activity::update()
        ->addWhere('id', '=', $this->activity['id'])
        ->addValue('activity_date_time', $values['scheduled_call_date'])
        ->addValue('status_id:name', 'Scheduled')
        ->addValue('civicall_call_details.civicall_schedule_date', $values['scheduled_call_date'])
        ->execute();

      CRM_Core_Session::setStatus(E::ts("Call is rescheduled!"), E::ts('Success'), 'success');
    }

    CallLogs::create()
      ->addValue('activity_id', $values['activity_id'])
      ->addValue('call_start_date', $startCallDate->format('Y-m-d H:i:s'))
      ->addValue('call_end_date', (new DateTime)->format('Y-m-d H:i:s'))
      ->addValue('created_by_contact_id', CRM_Core_Session::getLoggedInContactID())
      ->addValue('call_response_option_value_id', $values['preliminary_call_response'])
      ->execute();

    $callLogsCount = CivicallUtils::getActivityCallLogsCount($this->activity['id']);

    Activity::update()
      ->addValue('details', $values['notes'])
      ->addWhere('id', '=', $this->activity['id'])
      ->addValue('civicall_call_details.civicall_response_counter', $callLogsCount)
      ->execute();

    parent::postProcess();
  }

  public function addRules() {
    $this->addFormRule([self::class, 'validateForm']);
  }

  public static function validateForm($values) {
    $errors = [];

    $isRescheduleAction = isset($values[self::getRescheduleButtonName()]);
    $isCloseAndSaveAction = isset($values[self::getCloseAndSaveButtonName()]);

    if ($isRescheduleAction) {
      if (empty($values['scheduled_call_date'])) {
        $errors['scheduled_call_date'] = "Scheduled Call Date is required field!";
      }
      if (empty($values['preliminary_call_response'])) {
        $errors['preliminary_call_response'] = "Preliminary response is required field!";
      }
    }

    if ($isCloseAndSaveAction) {
      if (empty($values['response_call_date'])) {
        $errors['response_call_date'] = "Response Date is required field!";
      }
      if (empty($values['final_call_response'])) {
        $errors['final_call_response'] = "Final response response is required field!";
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  public static function getRescheduleButtonName() {
    return '_qf_CivicallCallCenter_next';
  }

  public static function getCloseAndSaveButtonName() {
    return '_qf_CivicallCallCenter_submit';
  }

  public static function getCloseAndWithoutSaveButtonName() {
    return '_qf_CivicallCallCenter_cancel';
  }

  public function setDefaultValues() {
    $defaults = [];

    $defaults['notes'] = $this->activity['details'];
    $defaults['response_call_date'] = (new DateTime())->format('Y-m-d H:i:s');

    $scheduleOffsets = $this->callCenterConfiguration->getScheduleOffsets();
    if (!empty($scheduleOffsets[$this->callLogsCount]['calculatedDate'])) {
      $defaults['scheduled_call_date'] = $scheduleOffsets[$this->callLogsCount]['calculatedDate'];
    } else {
      $defaults['scheduled_call_date'] = (new DateTime())->format('Y-m-d H:i:s');
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

}
