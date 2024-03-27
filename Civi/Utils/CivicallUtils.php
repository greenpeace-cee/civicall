<?php

namespace Civi\Utils;

use Civi\Api4\Activity;
use CRM_Core_PseudoConstant;
use CRM_Utils_System;
use DateTime;

class CivicallUtils {

  public static function getCivicallActivities($params) {
    $activities = \Civi\Api4\Activity::get()
      ->addSelect('id', 'subject')
      ->addWhere('activity_type_id:name', '=', CivicallSettings::OUTGOING_CALL_ACTIVITY_TYPE)
      ->execute();

    $preparedActivities = [];
    foreach ($activities as $activity) {
      $preparedActivities[] = [
        'id' => $activity['id'],
        'subject' => $activity['subject'],
        'callCenterLink' => CRM_Utils_System::url('civicrm/civicall/call-center', "reset=1&activity_id=" . $activity['id']),
        'editActivityLink' => CRM_Utils_System::url('civicrm/activity/add', "reset=1&action=update&id=" . $activity['id']),
      ];
    }

    return $preparedActivities;
  }

  /**
   * @param $activityId
   * @return array
   */
  public static function getCallCenterTargetContact($activityId) {
    $targetContact = [];
    if (empty($activityId)) {
      return $targetContact;
    }

    $contactId = CivicallUtils::getActivityTargetContactId($activityId);
    $contactDisplayName = '';
    $contact = \Civi\Api4\Contact::get()
      ->addSelect('display_name')
      ->addWhere('id', '=', $contactId)
      ->setLimit(1)
      ->execute()
      ->first();

    if (!empty($contact['display_name'])) {
      $contactDisplayName = $contact['display_name'];
    }

    $targetContact['id'] = $contactId;
    $targetContact['link'] = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid=" . $contactId);
    $targetContact['displayName'] = $contactDisplayName;
    $targetContact['phones'] = CivicallUtils::getTargetContactPhones($contactId);

    return $targetContact;
  }

  /**
   * @param $activityId
   * @return array
   */
  public static function getCallCenterTargetCampaign($activityId) {
    $targetCampaign = [];
    if (empty($activityId)) {
      return $targetCampaign;
    }

    $activity = \Civi\Api4\Activity::get()
      ->addSelect('campaign_id')
      ->addWhere('id', '=', $activityId)
      ->setLimit(1)
      ->execute()
      ->first();

    if (empty($activity['campaign_id'])) {
      return $targetCampaign;
    }

    $campaign = \Civi\Api4\Campaign::get()
      ->addSelect('title', 'id')
      ->addWhere('id', '=', $activity['campaign_id'])
      ->setLimit(1)
      ->execute()
      ->first();

    $targetCampaign['id'] = $campaign['id'];
    $targetCampaign['title'] = $campaign['title'];
    $targetCampaign['link'] = CRM_Utils_System::url('civicrm/campaign/add', "reset=1&action=update&id=" . $campaign['id']);

    return $targetCampaign;
  }

  public static function getTargetContactPhones($contactId) {
    $preparedPhones = [];
    if (empty($contactId)) {
      return $preparedPhones;
    }

    $phones = \Civi\Api4\Phone::get()
      ->addSelect('label', 'phone_numeric', 'is_primary', 'location_type_id:label')
      ->addWhere('contact_id', '=', $contactId)
      ->execute();

    foreach ($phones as $phone) {
      $preparedPhones[] = [
        'id' => $phone['id'],
        'phoneNumber' => $phone['phone_numeric'],
        'phoneTypeLabel' => $phone['location_type_id:label'],
        'isPrimary' => $phone['is_primary'],
      ];
    }

    return $preparedPhones;
  }

  public static function getCallCenterLogs($activityId) {
    $logs = [];
    if (empty($activityId)) {
      return $logs;
    }

    $callLogs = \Civi\Api4\CallLogs::get()
      ->addWhere('activity_id', '=', $activityId)
      ->execute();

    foreach ($callLogs as $callLog) {
      $contactId = $callLog['created_by_contact_id'];
      $contact = \Civi\Api4\Contact::get()
        ->addSelect('display_name')
        ->addWhere('id', '=', $contactId)
        ->setLimit(1)
        ->execute()
        ->first();

      $optionValue = \Civi\Api4\OptionValue::get()
        ->addSelect('label')
        ->addWhere('id', '=', $callLog['call_response_option_value_id'])
        ->setLimit(1)
        ->execute()
        ->first();

      $startCallDate = DateTime::createFromFormat('Y-m-d H:i:s', $callLog['call_start_date']);
      $endCallDate = DateTime::createFromFormat('Y-m-d H:i:s', $callLog['call_end_date']);
      $elapsedTimestamp = $endCallDate->getTimestamp() - $startCallDate->getTimestamp();
      $minutes = (int) ($elapsedTimestamp / 60);
      $seconds = $elapsedTimestamp % 60;
      $durationText = '';

      if ($minutes !== 0) {
        $durationText .= $minutes . ' minute' . (($minutes === 1) ? '' : 's') . ' ';
      }

      $durationText .= $seconds . ' second' . (($seconds === 1) ? '' : 's');

      $logs[] = [
        'created_by_display_name' => $contact['display_name'],
        'created_by_contact_Link' => CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid=" . $contactId),
        'formatted_start_date' => $callLog['call_start_date'],
        'duration' => $durationText,
        'responseLabel' => $optionValue['label'],
      ];
    }

    return $logs;
  }

  public static function getActivityTargetContactId($activityId)
  {
    if (empty($activityId)) {
      return NULL;
    }

    $targetContactRecordTypeId = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_ActivityContact', 'record_type_id', 'Activity Targets');

    $activityContact = \Civi\Api4\ActivityContact::get()
      ->addSelect('contact_id')
      ->addWhere('activity_id', '=', $activityId)
      ->addWhere('record_type_id', '=', $targetContactRecordTypeId)
      ->execute()
      ->first();

    if (!empty($activityContact['contact_id'])) {
      return $activityContact['contact_id'];
    }

    return NULL;
  }

  public static function getActivity($activityId) {
    if (empty($activityId)) {
      return [];
    }

    $activity = Activity::get()
      ->addSelect(
        'id',
        'details',
        'campaign_id.civicall_call_configuration.configuration',
        'campaign_id.civicall_call_configuration.script',
        'campaign_id.civicall_call_configuration.is_call_center_enabled'
      )
      ->addWhere('id', '=', $activityId)
      ->execute()
      ->first();

    if (!empty($activity)) {
      return [
        'id' => $activity['id'],
        'details' => $activity['details'],
        'campaignScript' => $activity['campaign_id.civicall_call_configuration.script'],
        'campaignConfiguration' => $activity['campaign_id.civicall_call_configuration.configuration'],
        'isCallCenterEnabled' => $activity['campaign_id.civicall_call_configuration.is_call_center_enabled'],
      ];
    }

    return [];
  }

  public static function getActivityCallLogsCount($activityId) {
    if (empty($activityId)) {
      return 0;
    }

    return \Civi\Api4\CallLogs::get()
      ->addWhere('activity_id', '=', $activityId)
      ->execute()
      ->count();
  }

}

