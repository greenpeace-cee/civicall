<?php

namespace Civi\Civicall\Utils;

use Exception;

class CallCenterActions {

  const RESCHEDULE_CALL = 'reschedule_call';
  const CLOSE_CALL = 'close_call';
  const REOPEN_CALL = 'reopen_call';
  const UPDATE_CALL_RESPONSE = 'update_call_response';

  private static $actionsButton = '_qf_CivicallCallCenter_submit';

  public static function getButtonName($actionName) {
    self::validateActionName($actionName);

    return static::$actionsButton . '[' . $actionName . ']';
  }

  public static function isAction($submitValues, $actionName) {
    self::validateActionName($actionName);

    if (isset($submitValues[static::$actionsButton][$actionName]) && $submitValues[static::$actionsButton][$actionName] == "1") {
      return true;
    }

    return false;
  }

  private static function validateActionName($actionName) {
    if (!in_array($actionName, [
      self::RESCHEDULE_CALL,
      self::CLOSE_CALL,
      self::REOPEN_CALL,
      self::UPDATE_CALL_RESPONSE,
    ])) {
      throw new Exception(CallCenterActions::class . ': Unexpected action.');
    }
  }

}
