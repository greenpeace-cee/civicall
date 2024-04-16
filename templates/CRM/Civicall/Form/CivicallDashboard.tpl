<div class="civicall__dashboard">

  <div class="civicall__dashboard-item">
    <div class="civicall__accordion crm-accordion-wrapper">
      <div class="crm-accordion-header crm-master-accordion-header">Call Configs</div>
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
    </div>
  </div>

  <div class="civicall__dashboard-item">
    <div class="civicall__accordion crm-accordion-wrapper">
      <div class="crm-accordion-header crm-master-accordion-header">Search Call activities:</div>
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
              <button class="civicall__button civicall--blue civicall--height-medium crm-form-submit validate crm-button crm-button-type-submit crm-button{$searchButtonName}" value="1" type="submit" name="{$searchButtonName}" id="{$searchButtonName}-bottom">
                <i aria-hidden="true" class="crm-i fa-search"></i>
                <span>SEARCH</span>
              </button>
            </div>
          </div>

          <div class="civicall__dashboard-results">
            <div class="call-center__sub-title">Results:</div>

            {foreach from=$civicallActivities item=activity}
              <ul>
                <li style="border-bottom: 1px solid grey; margin-bottom: 10px; max-width: 300px">
                  <div>id=[{$activity.id}]:{$activity.subject}</div>
                  <div>
                    <a class="crm-popup" href="{$activity.callCenterLink}">Call center link</a>
                  </div>
                  <div>
                    <a class="crm-popup" href="{$activity.editActivityLink}" target="_blank">Edit activity link</a>
                  </div>
                  <div>
                    <a class="crm-popup" href="{$activity.editCampaignLink}" target="_blank">Edit campaign link</a>
                  </div>
                </li>
              </ul>
            {/foreach}
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
<script src="{$callCenterJsUrl}"></script>
