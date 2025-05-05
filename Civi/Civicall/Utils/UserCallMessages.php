<?php

namespace Civi\Civicall\Utils;
use CRM_Civicall_ExtensionUtil as E;
use CRM_Core_Session;
use CRM_Utils_System;

class UserCallMessages {

  public static function makeScheduleCallMessage(int $callCenterActivityId, string $scheduleCallDateTime, array $preliminaryResponseOption): void {
    $message = E::ts('<div>Call with %1 re-scheduled for %2.</div> <div>Preliminary Response:"%4 %3".</div>', [
      1 => self::getLoggedInContactHtmlLink(),
      2 => $scheduleCallDateTime,
      3 => $preliminaryResponseOption['text'],
      4 => self::prepareResponseIcon($preliminaryResponseOption),
    ]);
    $message .= '<br/>' . self::getCallCenterHtmlMessageWithLink($callCenterActivityId);

    CRM_Core_Session::setStatus($message, E::ts("Call is rescheduled!"), 'success');
  }

  public static function makeCloseCallMessage(int $callCenterActivityId, array $finalCallResponseOption): void {
    $message = E::ts('<div>Call is closed by %1.</div><div> Response: "%3 %2".</div>', [
      1 => self::getLoggedInContactHtmlLink(),
      2 => $finalCallResponseOption['label'],
      3 => self::prepareResponseIcon($finalCallResponseOption),
    ]);
    $message .= '<br/>' . self::getCallCenterHtmlMessageWithLink($callCenterActivityId);

    CRM_Core_Session::setStatus($message, E::ts("Closed call and saved!"), 'success');
  }

  public static function makeReopenCallMessage(int $callCenterActivityId): void {
    $message = E::ts('<div>Call is reopened by %1.</div>', [
      1 => self::getLoggedInContactHtmlLink(),
    ]);
    $message .= '<br/>' . self::getCallCenterHtmlMessageWithLink($callCenterActivityId);

    CRM_Core_Session::setStatus($message, E::ts("Call is reopened!"), 'success');
  }

  public static function makeUpdateCallResponseMessage(int $callCenterActivityId, array $finalCallResponseOption): void {
    $message = E::ts('<div>Call response is updated by %1. New call response: "%3 %2"</div>', [
      1 => self::getLoggedInContactHtmlLink(),
      2 => $finalCallResponseOption['label'],
      3 => self::prepareResponseIcon($finalCallResponseOption),
    ]);
    $message .= '<br/>' . self::getCallCenterHtmlMessageWithLink($callCenterActivityId);

    CRM_Core_Session::setStatus($message, E::ts("Updated call response!"), 'success');
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

  private static function prepareResponseIcon(array $option): string {
    return (!empty($option['icon'])) ? '<i class="crm-i ' . $option['icon'] . '"></i>' : '';
  }

}
