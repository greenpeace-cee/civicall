<?php

namespace Civi\Civicall\Utils;

use Civi\Api4\Activity;
use Civi\Api4\CallLog;
use Civi\Api4\CustomField;
use CRM_Core_PseudoConstant;
use CRM_Utils_System;
use DateTime;
use DateTimeZone;
use Exception;

class CivicallUtils {

  public static function getCivicallActivities($params) {
    $activityEntity = Activity::get(FALSE);
    $activityEntity->addSelect('id', 'subject', 'campaign_id');
    $activityEntity->addWhere('activity_type_id:name', '=', CivicallSettings::OUTGOING_CALL_ACTIVITY_TYPE);

    if (!empty($params['limit'])) {
      $activityEntity->setLimit($params['limit']);
    }
    if (!empty($params['target_contact_id'])) {
      $targetContactRecordTypeId = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_ActivityContact', 'record_type_id', 'Activity Targets');
      $activityEntity->addJoin('ActivityContact AS activity_contact', 'LEFT', ['id', '=', 'activity_contact.activity_id']);
      $activityEntity->addWhere('activity_contact.record_type_id', '=', $targetContactRecordTypeId);
      $activityEntity->addWhere('activity_contact.contact_id', '=', $params['target_contact_id']);
    }

    $activities = $activityEntity->execute();

    $preparedActivities = [];
    foreach ($activities as $activity) {
      $responseActivities = [];
      $responseActivityIds = CivicallUtils::getRelatedResponseActivities($activity['id']);
      foreach ($responseActivityIds as $responseActivityId) {
        $responseActivities[] = [
          'id' => $responseActivityId,
          'link' => CRM_Utils_System::url('civicrm/activity/add', "reset=1&action=update&id=" . $responseActivityId),
        ];
      }

      $preparedActivities[] = [
        'id' => $activity['id'],
        'subject' => $activity['subject'],
        'callCenterLink' => CRM_Utils_System::url('civicrm/civicall/call-center', "reset=1&activity_id=" . $activity['id']),
        'editActivityLink' => CRM_Utils_System::url('civicrm/activity/add', "reset=1&action=update&id=" . $activity['id']),
        'editCampaignLink' => CRM_Utils_System::url('civicrm/campaign/add', "reset=1&action=update&id=" . $activity['campaign_id']),
        'responseActivities' => $responseActivities,
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
    $contact = \Civi\Api4\Contact::get(FALSE)
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

    $activity = \Civi\Api4\Activity::get(FALSE)
      ->addSelect('campaign_id')
      ->addWhere('id', '=', $activityId)
      ->setLimit(1)
      ->execute()
      ->first();

    if (empty($activity['campaign_id'])) {
      return $targetCampaign;
    }

    $campaign = \Civi\Api4\Campaign::get(FALSE)
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

    $phones = \Civi\Api4\Phone::get(FALSE)
      ->addSelect('label', 'phone', 'phone_numeric', 'is_primary', 'location_type_id:label', 'phone_type_id:label')
      ->addWhere('contact_id', '=', $contactId)
      ->addOrderBy('is_primary', 'DESC')
      ->execute();

    foreach ($phones as $phone) {
      $preparedPhones[] = [
        'id' => $phone['id'],
        'phoneNumber' => $phone['phone'],
        'phoneNumeric' => $phone['phone_numeric'],
        'locationTypeLabel' => $phone['location_type_id:label'],
        'phoneTypeLabel' => $phone['phone_type_id:label'],
        'isPrimary' => $phone['is_primary'],
      ];
    }

    return $preparedPhones;
  }

  public static function getActivityTargetContactId($activityId) {
    if (empty($activityId)) {
      return NULL;
    }

    $targetContactRecordTypeId = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_ActivityContact', 'record_type_id', 'Activity Targets');

    $activityContact = \Civi\Api4\ActivityContact::get(FALSE)
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

    $activity = Activity::get(FALSE)
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
        'id' => (int) $activity['id'],
        'details' => $activity['details'],
        'campaignScript' => $activity['campaign_id.civicall_call_configuration.script'],
        'campaignConfiguration' => $activity['campaign_id.civicall_call_configuration.configuration'],
        'isCallCenterEnabled' => $activity['campaign_id.civicall_call_configuration.is_call_center_enabled'],
      ];
    }

    return [];
  }

  public static function linkActivity($childActivityId, $parentActivityId) {
    $activity = Activity::update(FALSE);
    $activity->addWhere('id', '=', $childActivityId);

    if (CivicallUtils::isActivityHasHierarchyCustomField()) {
      $activity->addValue('activity_hierarchy.parent_activity_id', $parentActivityId);
    } else {
      $activity->addValue('parent_id', $parentActivityId);
    }

    $activity->execute();
  }

  public static function isCallAlreadyClosed($activityId) {
    $activity = Activity::get(FALSE)
      ->addSelect(  'status_id:name', 'activity_tmresponses.response')
      ->addWhere('id', '=', $activityId)
      ->execute()
      ->first();

    if (!empty($activity['activity_tmresponses.response'])) {
      return true;
    }

    if (!empty($activity['status_id:name'] === 'Completed')) {
      return true;
    }

    return false;
  }

  public static function convertTimestampToDateTimeObject($timestamp) {
    try {
      $dateTime = DateTime::createFromFormat('U', $timestamp);
    } catch (Exception $e) {
      throw new Exception('Wrong start call date');
    }
    if (!($dateTime instanceof DateTime)) {
      throw new Exception('Wrong start call date');
    }

    $currentTimeZone = (new DateTime)->getTimezone()->getName();
    $dateTime->setTimeZone(new DateTimeZone($currentTimeZone));

    return $dateTime;
  }

  public static function isActivityHasHierarchyCustomField() {
    $isActivityHasHierarchyCustomField = false;

    $customField = CustomField::get(FALSE)
      ->addWhere('custom_group_id:name', '=', 'activity_hierarchy')
      ->addWhere('name', '=', 'parent_activity_id')
      ->addWhere('custom_group_id.extends', '=', 'Activity')
      ->execute()
      ->first();

    if (!empty($customField)) {
      $isActivityHasHierarchyCustomField = true;
    }

    return $isActivityHasHierarchyCustomField;
  }

  public static function getRelatedResponseActivities($activityId) {
    $responseActivityIds = [];

    if (empty($activityId)) {
      return $responseActivityIds;
    }

    $activityEntity = Activity::get(FALSE);
    $activityEntity->addSelect('id', 'subject', 'campaign_id');
    $activityEntity->addWhere('activity_type_id:name', '=', CivicallSettings::RESPONSE_CALL_ACTIVITY_TYPE);

    if (CivicallUtils::isActivityHasHierarchyCustomField()) {
      $activityEntity->addWhere('activity_hierarchy.parent_activity_id', '=', $activityId);
    } else {
      $activityEntity->addWhere('parent_id', '=', $activityId);
    }

    $activities = $activityEntity->execute();

    foreach ($activities as $activity) {
      $responseActivityIds[] = $activity['id'];
    }

    if (count($responseActivityIds) > 1) {
      \Civi::log()->warning('Found more than one Response activity for Outgoing Call activity '. $activityId);
    }

    return $responseActivityIds;
  }

  public static function getLastCallLogId($activityId) {
    if (empty($activityId)) {
      return null;
    }

    $callLog = CallLog::get(FALSE)
      ->addSelect('id')
      ->addWhere('activity_id', '=', $activityId)
      ->addOrderBy('id', 'DESC')
      ->setLimit(1)
      ->execute()
      ->first();

    if (!empty($callLog['id'])) {
      return $callLog['id'];
    }

    return null;
  }

  public static function isOutgoingCallActivity($activityId) {
    if (empty($activityId)) {
      return false;
    }

    $activity = Activity::get(FALSE)
      ->addSelect('id')
      ->addWhere('activity_type_id:name', '=', CivicallSettings::OUTGOING_CALL_ACTIVITY_TYPE)
      ->addWhere('id', '=', $activityId)
      ->execute()
      ->first();

    if (!empty($activity['id'])) {
      return true;
    }

    return false;
  }

  public static function isStringContains($searchByString, $targetString) {
    $pos = strpos($targetString, $searchByString);

    if ($pos === false) {
      return false;
    }

    return true;
  }

  public static function isCallCentreEnabled($activityId) {
    if (empty($activityId)) {
      return false;
    }

    $isEnabledFieldName = 'campaign_id.civicall_call_configuration.is_call_center_enabled';

    $activity = Activity::get(FALSE)
      ->addSelect($isEnabledFieldName, 'id')
      ->addWhere('id', '=', $activityId)
      ->execute()
      ->first();

    if (isset($activity[$isEnabledFieldName]) && $activity[$isEnabledFieldName] === true) {
      return true;
    }

    return false;
  }

  public static function getContactDisplayName($contactId): string {
    $contact = \Civi\Api4\Contact::get(FALSE)
      ->addSelect('display_name')
      ->addWhere('id', '=', $contactId)
      ->setLimit(1)
      ->execute()
      ->first();

    if (!empty($contact['display_name'])) {
      return $contact['display_name'];
    }

    return '';
  }

}

