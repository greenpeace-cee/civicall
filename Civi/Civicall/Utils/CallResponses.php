<?php

namespace Civi\Civicall\Utils;

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

  public static function isValidResponseName($responseName) {
    CallResponses::setResponses();

    return !empty(CallResponses::$responses[$responseName]);
  }

  public static function getResponseOptions($neededResponseNames = []) {
    CallResponses::setResponses();
    $options = [];

    if (!is_array($neededResponseNames) || empty($neededResponseNames)) {
      foreach (CallResponses::$responses as $response) {
        $options[$response['id']] = $response['label'];
      }

      return $options;
    }

    foreach ($neededResponseNames as $responseName) {
      if (!empty(CallResponses::$responses[$responseName])) {
        $options[CallResponses::$responses[$responseName]['id']] = CallResponses::$responses[$responseName]['label'];
      }
    }

    return $options;
  }

  private static function setResponses() {
    if (!is_null(CallResponses::$responses)) {
      return;
    }

    $optionValues = OptionValue::get()
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

}
