<div id="bootstrap-theme" class="civicall__dashboard">
  <div class="crm-form-block">
    <div class="civicall__dashboard-item">
      <details class="crm-accordion-bold" open="open">
        <summary>
          <span>Call Configs</span>
        </summary>
        <div class="crm-accordion-body">
          <div class="civicall__dashboard-item-content">

            <div class="civicall__row">
              <div class="call-center__action-row-item">
                <div class="call-center__action-row-item-title">
                    {$form.available_responses.label}
                </div>
                <div class="call-center__action-row-item-content">
                    {$form.available_responses.html}
                  <div>
                    <a href="{$editCallResponsesLink}" target="_blank">Edit Call Responses</a>
                  </div>
                </div>
              </div>

              <div class="call-center__action-row-item">
                <div class="call-center__action-row-item-title">
                    {$form.call_config_example.label}
                </div>
                <div class="call-center__action-row-item-content">
                    {$form.call_config_example.html}
                </div>
              </div>
            </div>

          </div>
        </div>
      </details>
    </div>

    <div class="civicall__dashboard-item">
      <details class="crm-accordion-bold" open="open">
        <summary>
          <span>Search Call activities:</span>
        </summary>
        <div class="crm-accordion-body">
          <div class="civicall__dashboard-item-content">
            <div class="civicall__dashboard-filters">
              <div>
                <div class="call-center__sub-title">Filters</div>
                <div class="civicall__row">
                  <div class="call-center__action-row-item">
                    <div class="call-center__action-row-item-title">
                        {$form.activity_limit.label}
                    </div>
                    <div class="call-center__action-row-item-content">
                        {$form.activity_limit.html}
                    </div>
                  </div>

                  <div class="call-center__action-row-item">
                    <div class="call-center__action-row-item-title">
                        {$form.target_contact_id.label}
                    </div>
                    <div class="call-center__action-row-item-content">
                        {$form.target_contact_id.html}
                    </div>
                  </div>
                </div>
              </div>

              <div class="civicall__dashboard-submit-button-wrap">
                <button class="btn btn-primary cc__m-0" value="1" type="submit" name="{$searchButtonName}" id="{$searchButtonName}-bottom">
                  <i class="crm-i fa-search"></i>
                  <span>SEARCH</span>
                </button>
              </div>
            </div>

            <div class="civicall__dashboard-results">
              <div class="call-center__sub-title">Results:</div>

                {foreach from=$civicallActivities item=activity}
                  <div class="civicall__dashboard-result-item">
                    <div>
                      <div>id=[{$activity.id}]:{$activity.subject}</div>
                      <div class="civicall__dashboard-result-item-content">
                        <div>
                          <a class="crm-popup" href="{$activity.callCenterLink}">Call center link</a>
                        </div>
                        <div>
                          <a class="crm-popup" href="{$activity.editActivityLink}" target="_blank">Edit activity link</a>
                        </div>
                        <div>
                          <a class="crm-popup" href="{$activity.editCampaignLink}" target="_blank">Edit campaign link</a>
                        </div>

                        <div>
                          <div>Related response activities:</div>
                          <div class="civicall__dashboard-result-item-response-activities">
                              {if !empty($activity.responseActivities)}
                                  {foreach from=$activity.responseActivities item=responseActivity}
                                    <div style="padding-left: 10px">
                                      <a class="crm-popup" href="{$responseActivity.link}" target="_blank">id=[{$responseActivity.id}]</a>
                                    </div>
                                  {/foreach}
                              {else}
                                <div>can't find eny related response activities</div>
                              {/if}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                {/foreach}
            </div>
          </div>
        </div>
      </details>
    </div>
  </div>
</div>

<script src="{$callCenterJsUrl}"></script>
