<?php

namespace Civi\Utils;

class CivicallSettings {

  const OUTGOING_CALL_ACTIVITY_TYPE = 'Outgoing Call';
  const CALL_RESPONSE_LIMIT_DEFAULT_VALUE = 3;

  public static function getResponseLimitMessage($responseLimitConfiguration, $callLogsCount) {
    if ($callLogsCount === $responseLimitConfiguration) {
      return 'The response counter limit (' . $responseLimitConfiguration . ') is already reached.';
    }

    if ($callLogsCount > $responseLimitConfiguration ) {
      return 'The response counter limit (' . $responseLimitConfiguration . ') is already reached. Current is ' . $callLogsCount . '!. <br/>It is more than expected!';
    }

    if (($callLogsCount + 1) === $responseLimitConfiguration) {
      return 'The response counter limit (' . $responseLimitConfiguration . ') will be reached after this call.<br/> You should close the call after the current attempt.';
    }

    return '';
  }

}