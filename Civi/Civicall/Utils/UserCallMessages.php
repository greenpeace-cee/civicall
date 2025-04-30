<?php

namespace Civi\Civicall\Utils;
use CRM_Civicall_ExtensionUtil as E;
use CRM_Core_Session;
use CRM_Utils_System;

class UserCallMessages {

  public static function makeScheduleCallMessage(int $callCenterActivityId, $scheduleCallDateTime, string $preliminaryResponse): void {
    $message = E::ts('<div>Call with %1 re-scheduled for %2.</div> <div>Preliminary Response: %3.</div>', [
      1 => self::getLoggedInContactHtmlLink(),
      2 => $scheduleCallDateTime,
      3 => $preliminaryResponse,
    ]);
    $message .= self::getCallCenterHtmlMessageWithLink($callCenterActivityId);

    CRM_Core_Session::setStatus($message,E::ts("Call is rescheduled!"), 'success');
  }

  public static function makeCloseCallMessage(int $callCenterActivityId, $finalCallResponse): void {
    $message = E::ts('<div>Call is closed by %2.</div><div> Response: %3.</div>', [
      1 => self::getCallCenterLink($callCenterActivityId),
      2 => self::getLoggedInContactHtmlLink(),
      3 => $finalCallResponse,
    ]);
    $message .= self::getCallCenterHtmlMessageWithLink($callCenterActivityId);

    CRM_Core_Session::setStatus($message, E::ts("Closed call and saved!"), 'success');
  }

  public static function makeReopenCallMessage(int $callCenterActivityId): void {
    $message = E::ts('<div>Call is reopened by %2.</div>', [
      1 => self::getCallCenterLink($callCenterActivityId),
      2 => self::getLoggedInContactHtmlLink(),
    ]);
    $message .= self::getCallCenterHtmlMessageWithLink($callCenterActivityId);

    CRM_Core_Session::setStatus($message, E::ts("Call is reopened!"), 'success');
  }

  public static function makeUpdateCallResponseMessage(int $callCenterActivityId): void {
    $message = E::ts('<div>Call response is updated by %2.</div>', [
      1 => self::getCallCenterLink($callCenterActivityId),
      2 => self::getLoggedInContactHtmlLink(),
    ]);
    $message .= self::getCallCenterHtmlMessageWithLink($callCenterActivityId);

    CRM_Core_Session::setStatus($message, E::ts("Updated call response!"),'success');
  }

  private static function getLoggedInContactHtmlLink(): string {
    $contactLink = CRM_Utils_System::url('civicrm/contact/view/', ['reset' => '1', 'cid' => CRM_Core_Session::getLoggedInContactID()]);
    $contactDisplayName = CivicallUtils::getContactDisplayName(CRM_Core_Session::getLoggedInContactID());

    return '<a href="' . $contactLink . '" target="_blank">' . $contactDisplayName . '</a>';
  }

  private static function getCallCenterHtmlMessageWithLink(int $callCenterActivityId): string {
    return E::ts('<div>You can open this call <a href="%1" target="_blank">here</a>.</div>', [
      1 =>CRM_Utils_System::url('civicrm/civicall/call-center', "reset=1&activity_id={$callCenterActivityId}"),
    ]);
  }

}
