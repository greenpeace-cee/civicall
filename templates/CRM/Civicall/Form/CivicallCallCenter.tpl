<div id="bootstrap-theme" class="civicall__dashboard">
  <div class="crm-form-block">
    <div class="call-center__wrap {if $isFormInPopup} call-center__popup-from {/if}">
      <div class="call-center__form-title">
        <span>Calling </span>
        <a href="{$targetContact.link}" target="_blank">{$targetContact.displayName}</a>
        <span>in campaign:</span>
        <a href="{$targetCampaign.link}" target="_blank">{$targetCampaign.title}</a>
      </div>

      <div class="call-center__contact-info-wrap">
        <ul class="call-center__contact-info-phones">
          {foreach from=$targetContact.phones item=phone}
            <li class="call-center__contact-info-phone">
              <span><a href="tel:{$phone.phoneNumeric}">{$phone.phoneNumber}</a></span>
              <span>({$phone.phoneTypeLabel})</span>
              {if ($phone.isPrimary)}
                <span>*</span>
              {/if}
            </li>
          {/foreach}
        </ul>
      </div>

      <div class="call-center__call-logs-wrap">
        <div class="call-center__sub-title">Previous calls</div>

        {if (!empty($callLogs))}
          <div>
            {foreach from=$callLogs item=log}
              <div class="call-center__call-logs">
                <div class="call-center__call-logs-call-number">
                  <b>{$log.call_number}.</b>
                </div>
                <div class="call-center__call-logs-call-duration">
                  <span>{$log.formatted_start_date} ({$log.duration})</span>
                </div>
                <div class="call-center__call-logs-call-created-by">
                  <a href="{$log.created_by_contact_Link}" target="_blank">{$log.created_by_display_name}</a>
                </div>
                <div class="call-center__call-logs-call-response">
                  <span>{$log.responseLabel}</span>
                </div>
              </div>
            {/foreach}
          </div>
        {else}
          <span>Empty Call logs</span>
        {/if}
      </div>

      <div class="call-center__current-call-wrap {if !$isShowTimer} civicall--hide {/if}">
        <div class="call-center__sub-title">Current call</div>
        <div class="call-center__current-call-timer-wrap">
          <div class="call-center__current-call-timer">
            <span>Time:</span>
            <span id="callCenterCurrentCallTimer"></span>
          </div>
        </div>
      </div>

      {if (!empty($activity.campaignScript))}
        <div class="call-center__call-scripts-wrap">
          <details class="crm-accordion-bold">
            <summary>
              <span>Call Script</span>
            </summary>
            <div class="crm-accordion-body" open="open">
              <div class="call-center__call-scripts">
                  {$activity.campaignScript}
              </div>
            </div>
          </details>
        </div>
      {/if}

      {if (!empty($pageLoaderConfiguration))}
        <div class="call-center__dynamic-block-wrap">
          {foreach from=$pageLoaderConfiguration item=loader}
            <div class="call-center__dynamic-block-item">
              <details class="crm-accordion-bold" {if !$loader.isCollapsed} open="open"{/if}>
                <summary>
                  <span>{$loader.title}</span>
                </summary>
                <div class="crm-accordion-body">
                  <div class="call-center__dynamic-block">
                      {$loader.afformModuleHtml}
                  </div>
                </div>
              </details>
            </div>
          {/foreach}
        </div>
      {/if}

      <div class="call-center__call-results-block-wrap">
        <details class="crm-accordion-bold" open="open">
          <summary>
            <span>Save Results</span>
          </summary>
          <div class="crm-accordion-body">
            <div class="call-center__call-results-block">

                {include file="CRM/Civicall/Chanks/CallCenterNotes.tpl"}

                {if $isCallAlreadyClosed && !empty($alreadyClosedCallMessage)}
                  <div class="call-center__already-closed-call-message-wrap">
                    <div class="call-center__already-closed-call-message">
                      <div class="status">{$alreadyClosedCallMessage}</div>
                    </div>
                  </div>
                {/if}

              <div class="call-center__actions">
                  {if !$isCallAlreadyClosed}
                    <div class="call-center__action-wrap">
                      <div class="call-center__sub-title">Schedule Call</div>
                      <div class="call-center__action-row">
                        <div class="call-center__action-row-item">
                          <div class="call-center__action-row-item-title">
                              {$form.scheduled_call_date.label}
                          </div>
                          <div class="call-center__action-row-item-content">
                              {$form.scheduled_call_date.html}
                          </div>
                        </div>

                        <div class="call-center__action-row-item">
                          <div class="call-center__action-row-item-title">
                              {$form.preliminary_call_response.label}
                          </div>
                          <div class="call-center__action-row-item-content">
                              {$form.preliminary_call_response.html}
                          </div>
                        </div>

                        <div class="call-center__action-row-item">
                          <button class="btn btn-primary cc__m-0" value="1" type="submit" name="{$rescheduleCallButtonName}" id="{$rescheduleCallButtonName}-bottom">
                            <i aria-hidden="true" class="crm-i fa-calendar"></i>
                            <span>RE-RESCHEDULE CALL</span>
                          </button>
                        </div>
                      </div>
                        {if !empty($responseLimitMessage)}
                          <div class="call-center__message-wrap">
                            <div class="status">
                                {$responseLimitMessage}
                            </div>
                          </div>
                        {/if}
                    </div>
                  {/if}

                  {if !$isCallAlreadyClosed}
                    <div class="call-center__action-wrap">
                      <div class="call-center__sub-title">Close Call</div>
                      <div class="call-center__action-row">
                        <div class="call-center__action-row-item">
                          <div class="call-center__action-row-item-title">
                              {$form.response_call_date.label}
                          </div>
                          <div class="call-center__action-row-item-content">
                              {$form.response_call_date.html}
                          </div>
                        </div>

                        <div class="call-center__action-row-item">
                          <div class="call-center__action-row-item-title">
                              {$form.final_call_response.label}
                          </div>
                          <div class="call-center__action-row-item-content">
                              {$form.final_call_response.html}
                          </div>
                        </div>

                        <div class="call-center__action-row-item">
                          <button class="btn btn-success cc__green-btn cc__m-0" value="1" type="submit" name="{$closeCallButtonName}" id="{$closeCallButtonName}-bottom">
                            <i class="crm-i fa-check"></i>
                            <span>SAVE AND CLOSE CALL</span>
                          </button>
                        </div>
                      </div>
                    </div>
                  {/if}

                  {if $isCallAlreadyClosed}
                    <div class="call-center__action-wrap">
                      <div class="call-center__sub-title">Reopen Call</div>
                      <div class="call-center__action-row">
                        <div class="call-center__action-row-item">
                          <div class="call-center__action-row-item-title">
                              {$form.reopen_scheduled_call_date.label}
                          </div>
                          <div class="call-center__action-row-item-content">
                              {$form.reopen_scheduled_call_date.html}
                          </div>
                        </div>

                        <div class="call-center__action-row-item">
                          <button class="btn btn-secondary" value="1" type="submit" name="{$reopenCallButtonName}" id="{$reopenCallButtonName}-bottom">
                            <i class="crm-i fa-calendar"></i>
                            <span>SAVE AND REOPEN CALL</span>
                          </button>
                        </div>
                      </div>
                    </div>
                  {/if}

                  {if $isCallAlreadyClosed}
                    <div class="call-center__action-wrap">
                      <div class="call-center__sub-title">Update Call Response</div>
                      <div class="call-center__action-row">

                        <div class="call-center__action-row-item">
                          <div class="call-center__action-row-item-title">
                              {$form.new_final_call_response.label}
                          </div>
                          <div class="call-center__action-row-item-content">
                              {$form.new_final_call_response.html}
                          </div>
                        </div>

                        <div class="call-center__action-row-item">
                          <button class="btn btn-success" value="1" type="submit" name="{$updateCallResponseButtonName}" id="{$updateCallResponseButtonName}-bottom">
                            <i class="crm-i fa-check"></i>
                            <span>SAVE AND UPDATE CALL RESPONSE</span>
                          </button>
                        </div>
                      </div>
                    </div>
                  {/if}
              </div>

              <div style="display: none !important;">
                  {$form.start_call_time_timestamp.html}
                  {$form.activity_id.html}
              </div>

              <div class="call-center__buttons-wrap">
                <div class="crm-submit-buttons">
                  <button class="btn btn-danger" value="1" type="submit" name="{$cancelButtonName}" id="{$cancelButtonName}-bottom">
                    <i class="crm-i fa-window-close"></i>
                    <span>Cancel without saving</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </details>
      </div>
    </div>
  </div>
</div>

<script src="{$callCenterJsUrl}"></script>
{*
  This scripts have to be included inliene in template.
  If you include the script at form controller, it runs only onnce while opening first time popup with this form.
*}
{literal}
<script>
  (function (CRM, $) {
    $(document).ready(function() {
      let isShowTimer = {/literal}{if $isShowTimer}true{else}false{/if}{literal};

      initTimer(isShowTimer);

      function initTimer(isShowTimer) {
        if (!isShowTimer) {
          return;
        }

        const timerElement = $('#callCenterCurrentCallTimer');
        if (timerElement.length === 0) {
          return;
        }

        const startTimeMilliseconds = Date.now();

        civicallSetInterval('#callCenterCurrentCallTimer', function () {
          let message = '';
          let elapsedTimeMilliseconds = Date.now() - startTimeMilliseconds;
          let seconds = Math.floor(elapsedTimeMilliseconds / 1000) % 60;
          let minutes = Math.floor(elapsedTimeMilliseconds / 60000);

          if (minutes !== 0) {
            message += minutes + ' minute' + ((minutes === 1) ? '' : 's') + ' ';
          }

          message += seconds + ' second' + ((seconds === 1) ? '' : 's');
          timerElement.text(message);
        }, 200, 50);
      }

      // Wrap for setInterval loop
      // Clears interval when popup with this page is closed.
      // It checks every some iteration if exist target element.
      function civicallSetInterval(elementSelector, callback, interval, checkEveryIteration) {
        if ($(elementSelector).length === 0) {
          return;
        }

        let iteration = 0;
        const intervalId = setInterval(function () {
          if ((iteration % checkEveryIteration === 0) && $(elementSelector).length === 0) {
            clearInterval(intervalId);
          }
          callback();
          iteration++;
        }, interval);
      }
    });
  })(CRM, CRM.$);
</script>
{/literal}
