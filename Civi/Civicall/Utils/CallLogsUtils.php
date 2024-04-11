<?php

namespace Civi\Civicall\Utils;

use Civi\Api4\CallLogs;
use CRM_Utils_System;
use DateTime;

class CallLogsUtils {

  public static function getCallCenterLogs($activityId) {
    $logs = [];
    if (empty($activityId)) {
      return $logs;
    }

    $callCount = 1;
    $callLogs = CallLogs::get()
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
        'call_number' => $callCount,
      ];
      $callCount++;
    }

    return $logs;
  }

  public static function getActivityCallLogsCount($activityId) {
    if (empty($activityId)) {
      return 0;
    }

    return CallLogs::get()
      ->addWhere('activity_id', '=', $activityId)
      ->execute()
      ->count();
  }

}
