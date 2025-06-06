<?php

namespace Civi\Civicall\Utils;

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

    if (isset($configurationJson['finalResponseOptionDefault'])) {
      $preparedConfiguration['finalResponse']['defaultResponseName'] = $this->prepareDefaultResponseName(
        $configurationJson['finalResponseOptionDefault'],
        'finalResponseOptionDefault',
        $preparedConfiguration['finalResponse']['names']
      );
    } else {
      $preparedConfiguration['finalResponse']['defaultResponseName'] = null;
    }

    if (isset($configurationJson['preliminaryResponseOptionDefault'])) {
      $preparedConfiguration['preliminaryResponse']['defaultResponseName'] = $this->prepareDefaultResponseName(
        $configurationJson['preliminaryResponseOptionDefault'],
        'preliminaryResponseOptionDefault',
        $preparedConfiguration['preliminaryResponse']['names']
      );
    } else {
      $preparedConfiguration['preliminaryResponse']['defaultResponseName'] = null;
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

      $isCollapsed = false;
      if (isset($pageConfig['isCollapsed']) && in_array($pageConfig['isCollapsed'], [1, "1", true, "true"], true)) {
        $isCollapsed = true;
      } elseif (isset($pageConfig['isCollapsed']) && in_array($pageConfig['isCollapsed'], [0, "0", false, "false"], true)) {
        $isCollapsed = false;
      }

      $afformLoader = new AfformLoader($pageConfig['afformModuleName'], []);
      if (!$afformLoader->isAfformExist()) {
        $this->setWarningMessage('Afform Module "'.  $pageConfig['afformModuleName'] . '" doesn\'t exist.');
      }

      $afformModuleParams = [];
      if (!empty($pageConfig['afformModuleParams']) && is_array($pageConfig['afformModuleParams'])) {
        foreach ($pageConfig['afformModuleParams']  as $paramName => $paramValue) {
          if (is_string($paramName) && (is_string($paramValue) || is_numeric($paramValue))) {
            $afformModuleParams[$paramName] = $paramValue;
          }
        }
      }

      $preparedPageLoader[] = [
        'title' => $pageConfig['title'],
        'afformModuleName' => $pageConfig['afformModuleName'],
        'afformModuleParams' => $afformModuleParams,
        'isCollapsed' => $isCollapsed,
        'afformModuleHtml' => "AfformModule " . $pageConfig['afformModuleName'] . " is doesn't load!",
      ];
    }

    return $preparedPageLoader;
  }

  /**
   * Loads afform modules scripts and render template for it
   *
   * @param $dynamicAfformModuleParams
   * @return void
   */
  public function loadAfformModules($dynamicAfformModuleParams) {
    $validateDynamicAfformModuleParams = [];
    if (!empty($dynamicAfformModuleParams) && is_array($dynamicAfformModuleParams)) {
      foreach ($dynamicAfformModuleParams as $dynamicParamName => $dynamicParamValue) {
        if ((is_string($dynamicParamValue) || is_numeric($dynamicParamValue))) {
          $validateDynamicAfformModuleParams[$dynamicParamName] = $dynamicParamValue;
        }
      }
    }

    foreach ($this->configuration['pageLoader'] as $key => $loader) {
      $finalAfformModuleParams = $validateDynamicAfformModuleParams;
      foreach ($loader['afformModuleParams'] as $paramName => $paramValue) {
        $finalAfformModuleParams[$paramName] = $paramValue;
      }

      $afformLoader = new AfformLoader($loader['afformModuleName'], $finalAfformModuleParams);
      $afformTemplateHtml = $afformLoader->getTemplate();
      $afformLoader->loadAngularjsModule();

      $this->configuration['pageLoader'][$key]['afformModuleParams'] = $finalAfformModuleParams;
      $this->configuration['pageLoader'][$key]['afformModuleHtml'] = $afformTemplateHtml;
    }
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

  private function prepareDefaultResponseName($fieldValue, $fieldName, $availableResponseNames) {
    if (!empty($fieldValue) && is_string($fieldValue)) {
      if (CallResponses::isValidResponseName($fieldValue)) {
        if (in_array($fieldValue, $availableResponseNames) || in_array('*', $availableResponseNames)) {
          return $fieldValue;
        } else {
          $this->setWarningMessage('Not valid value of "' . $fieldName . '": "' . $fieldValue . '". This value is not allow. Add this response name to available response names.');
        }
      } else {
        $this->setWarningMessage('Not valid value of "' . $fieldName . '": "' . $fieldValue . '".');
      }
    } else {
      $this->setWarningMessage('Not valid value of "' . $fieldName . '".');
    }

    return null;
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

  public function getFinalResponseNames() {
    return $this->configuration['finalResponse']['names'];
  }

  public function getAvailableResponseOptions() {
    return $this->configuration['finalResponse']['options'];
  }

  public function getFinalResponseDefaultResponseName() {
    return $this->configuration['finalResponse']['defaultResponseName'];
  }

  public function getPreliminaryResponseNames() {
    return $this->configuration['preliminaryResponse']['names'];
  }

  public function getPreliminaryResponseDefaultResponseName() {
    return $this->configuration['preliminaryResponse']['defaultResponseName'];
  }

  public function getPreliminaryResponseOptions() {
    return $this->configuration['preliminaryResponse']['options'];
  }

  public function getAllConfiguration() {
    return $this->configuration;
  }

}
