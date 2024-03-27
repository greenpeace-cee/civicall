<?php

namespace Civi\Utils;

use DateTime;
use Dompdf\Exception;

class CallCenterConfiguration {

  private $configuration = [];
  private $warningMessages = [];
  private $errorMessages = [];

  public function __construct(string $configurationJsonString) {
    $this->configuration = $this->getPreparedConfiguration($configurationJsonString);
  }

  private function getPreparedConfiguration(string $configurationJsonString) {
    $preparedConfiguration = [];
    $configurationJson = json_decode($configurationJsonString, true);

    if (is_null($configurationJson)) {
      $this->setError('Cannot parse CallCenter Configuration JSON.');
      return $preparedConfiguration;
    }

    $preparedConfiguration['pageLoader'] = $this->preparePageLoaderConfiguration($configurationJson);

    if (isset($configurationJson['finalResponseNames'])) {
      $preparedConfiguration['finalResponseNames'] = $this->prepareResponseNames($configurationJson['finalResponseNames']);
    } else {
      $preparedConfiguration['finalResponseNames'] = [];
    }

    if (isset($configurationJson['preliminaryResponseNames'])) {
      $preparedConfiguration['preliminaryResponseNames'] = $this->prepareResponseNames($configurationJson['preliminaryResponseNames']);
    } else {
      $preparedConfiguration['preliminaryResponseNames'] = [];
    }

    if (!empty($configurationJson['scheduleOffsets'])) {
      $preparedConfiguration['scheduleOffsets'] = $this->prepareScheduleOffsets($configurationJson['scheduleOffsets']);
    } else {
      $preparedConfiguration['scheduleOffsets'] = [];
    }

    $preparedConfiguration['isShowTimer'] = false;
    if (isset($configurationJson['isShowTimer']) && in_array($configurationJson['isShowTimer'], [1, "1", true, "true"], true)) {
      $preparedConfiguration['isShowTimer'] = true;
    }

    $preparedConfiguration['responseLimit'] = CivicallSettings::CALL_RESPONSE_LIMIT_DEFAULT_VALUE;
    if (isset($configurationJson['responseLimit'])
      && is_numeric($configurationJson['responseLimit'])
      && $configurationJson['responseLimit'] >= 1
    ) {
      $preparedConfiguration['responseLimit'] = (int) $configurationJson['responseLimit'];
    }

    return $preparedConfiguration;
  }

  private function preparePageLoaderConfiguration($configurationJson) {
    $preparedPageLoader = [];
    if (!isset($configurationJson['pageLoader'])) {
      return $preparedPageLoader;
    }
    $rawPageLoader = $configurationJson['pageLoader'];

    if (empty($rawPageLoader)) {
      return $preparedPageLoader;
    }

    if (!is_array($rawPageLoader)) {
      $this->setError('Wrong structure of pageLoader config. pageLoader is not array.');
    }

    foreach ($rawPageLoader as $pageConfig) {
      if (!isset($pageConfig['afformModuleName'])) {
        $this->setError('Wrong structure of pageLoader config. "afformModuleName" field is required for pageLoader item.');
      }

      $isCollapsed = true;
      if (isset($pageConfig['isCollapsed'])) {
        $isCollapsed = $pageConfig['isCollapsed'];
      }

      $preparedPageLoader[] = [
        'title' => $pageConfig['title'],
        'afformModuleName' => $pageConfig['afformModuleName'],
        'isCollapsed' => $isCollapsed,
      ];
    }

    return $preparedPageLoader;
  }

  private function prepareScheduleOffsets($rawScheduleOffsets) {
    $scheduleOffsets = [];
    if (!is_array($rawScheduleOffsets)) {
      $this->setWarningMessage('"scheduleOffsets" has to be array with offsets(string).');
      return $scheduleOffsets;
    }

    foreach ($rawScheduleOffsets as $rawScheduleOffset) {
      if (is_string($rawScheduleOffset)) {
        $parts = explode(':', $rawScheduleOffset);

        if (!isset($parts[0]) || !isset($parts[1])) {
          $this->setWarningMessage('Not valid structure of date offset in "ScheduleOffset" item:' .  $rawScheduleOffset);
          continue;
        }

        $callNumber = $parts[0];
        $offset = $parts[1];

        if (!is_numeric($callNumber)) {
          $this->setWarningMessage('Not valid structure of date offset in "ScheduleOffset" item:' .  $rawScheduleOffset);
          continue;
        }

        $dateTime = new DateTime();

        try {
          $dateTime->modify($offset);
        } catch (Exception $e) {
          $this->setWarningMessage('Not valid date offset in "ScheduleOffset" items:' .  $rawScheduleOffset . ', offset:' . $offset);
          continue;
        }
        $scheduleOffsets[$callNumber] = [
          'calculatedDate' => $dateTime->format('Y-m-d H:i:s'),
          'offset' => $offset,
          'callNumber' => $offset,
        ];
      }
    }

    return $scheduleOffsets;
  }

  private function prepareResponseNames($rawResponseNames) {
    $responseNames = [];
    if (empty($rawResponseNames)) {
      return $responseNames;
    }

    if (!is_array($rawResponseNames)) {
      $this->setWarningMessage('Response options has to be array of response option names!');
      return $responseNames;
    }

    foreach ($rawResponseNames as $name) {
      if (CallResponses::isValidResponseName($name)) {
        $responseNames[] = $name;
      } else {
        $this->setWarningMessage('Not valid response option name: "' . $name . '".');
      }
    }

    return $responseNames;
  }

  public function isHasErrors() {
    return !empty($this->errorMessages);
  }

  public function isHasWarnings() {
    return !empty($this->warningMessages);
  }

  public function getErrors() {
    return implode('<br/><br/>', $this->errorMessages);
  }

  public function getWarnings() {
    return implode('<br/><br/>', $this->warningMessages);
  }

  public function setError($message) {
    $this->errorMessages[] = $message;
  }

  private function setWarningMessage($message) {
    $this->warningMessages[] = $message;
  }

  public function getPageLoader() {
    return $this->configuration['pageLoader'];
  }

  public function getScheduleOffsets() {
    return $this->configuration['scheduleOffsets'];
  }
  public function getResponseLimit() {
    return $this->configuration['responseLimit'];
  }

  public function getIsShowTimer() {
    return $this->configuration['isShowTimer'];
  }

  /**
   * Get Available Final Response OptionValue Names
   * *
   * * @return array
   */
  public function getAvailableResponseOptionValueNames() {
    return $this->configuration['finalResponseNames'];
  }

  /**
   * Get Available Preliminary Response OptionValue Names
   *
   * @return array
   */
  public function getPreliminaryResponseNames() {
    return $this->configuration['preliminaryResponseNames'];
  }

  public function getAllConfiguration() {
    return $this->configuration;
  }

}
