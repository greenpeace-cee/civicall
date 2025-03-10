<?php

namespace Civi\Civicall\Utils;

use Civi\Api4\OptionGroup;
use Civi\Api4\OptionValue;

class CallResponses {

  const RESPONSES_OPTION_GROUP_NAME = 'response';

  private static $responses = NULL;

  /**
   * @param $responseOptionValueId
   * @return string
   */
  public static function getResponseValueById($responseOptionValueId) {
    CallResponses::setResponses();

    $optionValueValue = NULL;
    foreach (CallResponses::$responses as $responses) {
      if ($responses['id'] == $responseOptionValueId) {
        $optionValueValue = $responses['value'];
      }
    }

    return $optionValueValue;
  }

  /**
   * @param $responseOptionValueName
   * @return string
   */
  public static function getResponseValueByName($responseOptionValueName) {
    CallResponses::setResponses();

    $optionValueValue = NULL;
    foreach (CallResponses::$responses as $responses) {
      if ($responses['name'] == $responseOptionValueName) {
        $optionValueValue = $responses['value'];
      }
    }

    return $optionValueValue;
  }

  /**
   * @param $responseOptionValueValue
   * @return array|null
   */
  public static function getResponseByValue($responseOptionValueValue) {
    CallResponses::setResponses();

    foreach (CallResponses::$responses as $response) {
      if ($response['value'] == $responseOptionValueValue) {
        return $response;
      }
    }

    return null;
  }

  public static function isValidResponseName($responseName) {
    CallResponses::setResponses();

    return !empty(CallResponses::$responses[$responseName]);
  }

  public static function getResponseOptions($neededResponseNames = []) {
    CallResponses::setResponses();
    $select2Options = [];

    if (!is_array($neededResponseNames) || empty($neededResponseNames)) {
      foreach (CallResponses::$responses as $response) {
        $select2Options[] = [
          'id' => $response['value'],
          'text' => $response['label'],
        ];
      }

      return $select2Options;
    }

    foreach ($neededResponseNames as $responseName) {
      if (!empty(CallResponses::$responses[$responseName])) {
        $select2Options[] = [
          'id' => CallResponses::$responses[$responseName]['value'],
          'text' => CallResponses::$responses[$responseName]['label'],
        ];
      }
    }

    return $select2Options;
  }

  private static function setResponses() {
    if (!is_null(CallResponses::$responses)) {
      return;
    }

    $optionValues = OptionValue::get(FALSE)
      ->addSelect('id', 'label', 'name', 'value')
      ->addWhere('option_group_id:name', '=', CallResponses::RESPONSES_OPTION_GROUP_NAME)
      ->execute();

    CallResponses::$responses = [];

    foreach ($optionValues as $optionValue) {
      CallResponses::$responses[$optionValue['name']] = [
        'id' => $optionValue['id'],
        'label' => $optionValue['label'],
        'name' => $optionValue['name'],
        'value' => $optionValue['value'],
      ];
    }
  }

  public static function getAvailableResponsesNames() {
    CallResponses::setResponses();
    $responseNames = [];

    foreach (CallResponses::$responses as $response) {
      $responseNames[] = $response['name'];
    }

    return $responseNames;
  }

  public static function getCallResponsesOptionGroupId() {
    $optionGroup = OptionGroup::get(FALSE)
      ->addSelect('id')
      ->addWhere('name', '=', CallResponses::RESPONSES_OPTION_GROUP_NAME)
      ->execute()
      ->first();


    if (empty($optionGroup['id'])) {
      return null;
    }

    return $optionGroup['id'];
  }

}
