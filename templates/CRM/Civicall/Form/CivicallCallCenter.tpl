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
          <span>{$phone.phoneNumber}</span>
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
      <div class="call-center__call-logs-table-wrap">
          <table class="civicall__table table--border-less table--padding-less call-center__call-logs-table">
            {foreach from=$callLogs item=log}
              <tr>
                <td>
                  <span>{$log.formatted_start_date} ({$log.duration})</span>
                </td>
                <td>
                  <a href="{$log.created_by_contact_Link}" target="_blank">{$log.created_by_display_name}</a>
                </td>
                <td>
                  <span>{$log.responseLabel}</span>
                </td>
              </tr>
            {/foreach}
          </table>
      </div>
    {else}
      <span>Empty Call logs</span>
    {/if}
  </div>

  <div class="call-center__current-call-wrap {if !$isShowTimer} civicall--hide {/if}">
    <div class="call-center__sub-title">Current call</div>
    <div class="call-center__current-call-timer-wrap">
      <div class="call-center__current-call-timer">
        <span>Time left:</span>
        <span id="callCenterCurrentCallTimer"></span>
      </div>
    </div>
  </div>

  <div class="call-center__call-scripts-wrap">
    <div class="civicall__accordion crm-accordion-wrapper collapsed">
      <div class="crm-accordion-header crm-master-accordion-header">Call Scripts</div>
      <div class="crm-accordion-body">
        <div class="call-center__call-scripts">
          {$activity.campaignScript}
        </div>
      </div>
    </div>
  </div>

  <div class="call-center__dynamic-block-wrap">
    <div id="callCenterDynamicContent"></div>
  </div>

  <div class="call-center__call-results-block-wrap">
    <div class="civicall__accordion crm-accordion-wrapper">
      <div class="crm-accordion-header crm-master-accordion-header">Save Results</div>
      <div class="crm-accordion-body">
        <div class="call-center__call-results-block">

          <div class="call-center__notes-wrap">
            <div class="call-center__sub-title">Notes</div>
            <div class="call-center__notes-textarea-wrap">
              {$form.notes.html}
            </div>
          </div>

          <div class="call-center__actions">
            <div class="call-center__action-wrap">
              <div class="call-center__sub-title">Reschedule Call</div>
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
                  <button class="civicall__button civicall--blue civicall--height-medium crm-form-submit validate crm-button crm-button-type-next crm-button{$rescheduleButtonName}" value="1" type="submit" name="{$rescheduleButtonName}" id="{$rescheduleButtonName}-bottom">
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
                  <button class="civicall__button civicall--green civicall--height-medium crm-form-submit validate crm-button crm-button-type-submit crm-button{$closeAndSaveButtonName}" value="1" type="submit" name="{$closeAndSaveButtonName}" id="{$closeAndSaveButtonName}-bottom">
                    <i aria-hidden="true" class="crm-i fa-check"></i>
                    <span>SAVE AND CLOSE CALL</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          {$form.start_call_time_timestamp.html}
          {$form.current_activity_id.html}

          <div class="call-center__buttons-wrap">
            <div class="crm-submit-buttons">
              <button class="civicall__button civicall--red civicall--height-big crm-form-submit cancel crm-button crm-button-type-cancel crm-button{$closeAndWithoutSaveButtonName}" value="1" type="submit" name="{$closeAndWithoutSaveButtonName}" id="{$closeAndWithoutSaveButtonName}-bottom">
                <i aria-hidden="true" class="crm-i fa-window-close"></i>
                <span>Close call without save</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{*
  This scripts have to be included inliene in template.
  If you include the script at form controller, it runs only onnce while opening first time popup with this form.
*}
{literal}
<script>
  (function (CRM, $) {
    $(document).ready(function() {
      let pageLoaderConfiguration = {/literal}{$pageLoaderConfiguration|@json_encode nofilter}{literal};
      let isShowTimer = {/literal}{if $isShowTimer}true{else}false{/if}{literal};

      initTimer(isShowTimer);
      initHandlerDynamicContent(pageLoaderConfiguration);

      function initHandlerDynamicContent(pageLoaderConfiguration) {
        const dynamicContentElement = $('#callCenterDynamicContent');
        if (dynamicContentElement.length === 0) {
          return;
        }

        pageLoaderConfiguration.forEach((pageConfig, index) => {
          appendDynamicContent(dynamicContentElement, pageConfig, index)
        });
      }

      function appendDynamicContent(dynamicContentElement, pageConfig, pageConfigId) {
        let pageConfigElementId = 'pageConfigId_' + pageConfigId;
        let classes = '';
        if (pageConfig.isCollapsed) {
          classes += 'collapsed';
        }

        dynamicContentElement.append("" +
          '<div class="call-center__dynamic-block-item">' +
            '<div class="civicall__accordion crm-accordion-wrapper ' + classes + '">' +
              '<div class="crm-accordion-header crm-master-accordion-header">' + pageConfig.title + '</div>' +
                '<div class="crm-accordion-body">' +
                  '<div class="call-center__dynamic-block">' +
                    '<div id="' + pageConfigElementId + '">' +
                      'In develop' +
                    '</div>' +
                  '</div>' +
                '</div>' +
              '</div>' +
            '</div>' +
          '</div>');

        let pageContentElement = dynamicContentElement.find('#' + pageConfigElementId);
        // TODO: search kit forms
      }

      function initTimer(isShowTimer) {
        if (!isShowTimer) {
          return;
        }

        const timerElement = $('#callCenterCurrentCallTimer');
        if (timerElement.length === 0) {
          return;
        }

        const startTimeMilliseconds = Date.now();
        let iteration = 0;

        const intervalId = setInterval(function () {
          // Clears interval when popup with this page is closed.
          // It checks every 50 iteration if exist timer element.
          if (iteration % 50) {
            if ($('#callCenterCurrentCallTimer').length === 0) {
              clearInterval(intervalId);
            }
          }

          let elapsedTimeMilliseconds = Date.now() - startTimeMilliseconds;
          let elapsedTimeSeconds = (elapsedTimeMilliseconds / 1000).toFixed(0);
          let minutes = parseInt((elapsedTimeSeconds / 60).toFixed(0));
          let seconds = elapsedTimeSeconds % 60;
          let message = '';

          if (minutes !== 0) {
            message += minutes + ' minute' + ((minutes === 1) ? '' : 's') + ' ';
          }

          message += seconds + ' second' + ((seconds === 1) ? '' : 's');
          timerElement.text(message);
          iteration++;
        }, 200);
      }
    });
  })(CRM, CRM.$);
</script>
{/literal}
