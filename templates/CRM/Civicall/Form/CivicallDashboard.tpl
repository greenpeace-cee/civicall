<div class="civicall__dashboard">
  <div class="civicall__dashboard-filters">
{*    <div class="crm-submit-buttons">*}
{*      {include file="CRM/common/formButtons.tpl" location="top"}*}
{*    </div>*}
  </div>
  <div class="civicall__dashboard-results">
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
