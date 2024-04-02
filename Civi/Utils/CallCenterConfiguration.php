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
      $preparedConfiguration['finalResponse'] = $this->prepareResponseNames($configurationJson['finalResponseNames']);
    } else {
      $preparedConfiguration['finalResponse'] = [
        'names' => ['*'],
        'options' => CallResponses::getResponseOptions(),
      ];
    }

    if (isset($configurationJson['preliminaryResponseNames'])) {
      $preparedConfiguration['preliminaryResponse'] = $this->prepareResponseNames($configurationJson['preliminaryResponseNames']);
    } else {
      $preparedConfiguration['preliminaryResponse'] = [
        'names' => ['*'],
        'options' => CallResponses::getResponseOptions(),
      ];
    }

    if (!empty($configurationJson['scheduleOffsets'])) {
      $preparedConfiguration['scheduleOffsets'] = $this->prepareScheduleOffsets($configurationJson['scheduleOffsets']);
    } else {
      $preparedConfiguration['scheduleOffsets'] = [];
    }

    if (isset($configurationJson['isShowTimer']) && in_array($configurationJson['isShowTimer'], [1, "1", true, "true"], true)) {
      $preparedConfiguration['isShowTimer'] = true;
    } elseif (isset($configurationJson['isShowTimer']) && in_array($configurationJson['isShowTimer'], [0, "0", false, "false"], true)) {
      $preparedConfiguration['isShowTimer'] = false;
    } else {
      $this->setWarningMessage('"isShowTimer" filed has invalid value. This field uses default value.');
      $preparedConfiguration['isShowTimer'] = false;
    }

    if (isset($configurationJson['responseLimit'])
      && is_numeric($configurationJson['responseLimit'])
      && $configurationJson['responseLimit'] >= 1
    ) {
      $preparedConfiguration['responseLimit'] = (int) $configurationJson['responseLimit'];
    } else {
      $this->setWarningMessage('"responseLimit" filed has invalid value. This field uses default value.');
      $preparedConfiguration['responseLimit'] = CivicallSettings::CALL_RESPONSE_LIMIT_DEFAULT_VALUE;
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

  private function prepareScheduleOffsets($rawScheduleOffsetItems) {
    $scheduleOffsets = [];
    $exampleMessage = '  Example: "scheduleOffsets": [{"callNumber" : 1,"dateModify" : "+3 days"}]';
    $skipItemMessage = '  The "scheduleOffsets" item will be skipped!';

    if (!is_array($rawScheduleOffsetItems)) {
      $this->setWarningMessage('"scheduleOffsets" has to be array with offsets.' . $exampleMessage);
      return $scheduleOffsets;
    }

    foreach ($rawScheduleOffsetItems as $rawScheduleOffsetItem) {
      $currentValueMessage = ' Current value: "' .  json_encode($rawScheduleOffsetItem) . '". ';

      if (!is_array($rawScheduleOffsetItem) || !isset($rawScheduleOffsetItem['callNumber']) || !isset($rawScheduleOffsetItem['dateModify'])) {
        $this->setWarningMessage('Wrong structure of "scheduleOffsets" item.' . $skipItemMessage.  $currentValueMessage . $exampleMessage);
        continue;
      }

      $callNumber = $rawScheduleOffsetItem['callNumber'];
      $dateModify = $rawScheduleOffsetItem['dateModify'];

      if (!is_numeric($callNumber)) {
        $this->setWarningMessage('"callNumber" value have to be integer.' . $skipItemMessage . $currentValueMessage . $exampleMessage);
        continue;
      }

      if (!is_string($dateModify)) {
        $this->setWarningMessage('"dateModify" value have to be string.' . $skipItemMessage . $currentValueMessage . $exampleMessage);
        continue;
      }

      $dateTime = new DateTime();

      try {
        // TODO: try to hide warning message when not valid modify date, example value:  "+30 sadsadasd"
        // TODO: In new version of PHP, modify have to throws an exception instead of a warning
        $isValidDate = $dateTime->modify($dateModify);
      } catch (Exception $e) {
        $this->setWarningMessage('"dateModify" value is not valid. Cannot create date with this date!' . $skipItemMessage . $currentValueMessage . $exampleMessage);
        continue;
      }

      if ($isValidDate === false) {
        $this->setWarningMessage('"dateModify" is not valid.' . $skipItemMessage . $currentValueMessage . $exampleMessage);
        continue;
      }

      $scheduleOffsets[$callNumber] = [
        'calculatedDate' => $dateTime->format('Y-m-d H:i:s'),
        'dateModify' => $dateModify,
        'callNumber' => $callNumber,
      ];
    }

    return $scheduleOffsets;
  }

  private function prepareResponseNames($rawResponseNames) {
    $currentValueMessage = 'Current values: ' . json_encode($rawResponseNames);
    $responseNames = [];
    if (empty($rawResponseNames)) {
      return $responseNames;
    }

    if (!is_array($rawResponseNames)) {
      $this->setWarningMessage('Response options has to be array of response option names!' . $currentValueMessage);
      return $responseNames;
    }

    $isThereInvalidOptions = false;
    foreach ($rawResponseNames as $name) {
      if (CallResponses::isValidResponseName($name)) {
        $responseNames[] = $name;
      } else {
        $isThereInvalidOptions = true;
        $this->setWarningMessage('Not valid response option name: "' . $name . '".');
      }
    }

    if ($isThereInvalidOptions && empty($responseNames)) {
      $options = [];
      $this->setWarningMessage("Response options doesn't have any valid option. " . $currentValueMessage);
    } elseif(empty($responseNames)) {
      $options = [];
    } else {
      $options = CallResponses::getResponseOptions($responseNames);
    }

    return [
      'names' => $responseNames,
      'options' => $options,
    ];
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
    return $this->configuration['finalResponse']['names'];
  }

  public function getAvailableResponseOptions() {
    return $this->configuration['finalResponse']['options'];
  }

  /**
   * Get Available Preliminary Response OptionValue Names
   *
   * @return array
   */
  public function getPreliminaryResponseNames() {
    return $this->configuration['preliminaryResponse']['names'];
  }

  public function getPreliminaryResponseOptions() {
    return $this->configuration['preliminaryResponse']['options'];
  }

  public function getAllConfiguration() {
    return $this->configuration;
  }

}
