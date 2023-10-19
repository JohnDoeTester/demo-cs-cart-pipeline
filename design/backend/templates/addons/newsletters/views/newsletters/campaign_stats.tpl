<div id="content_campaign_stats_{$campaign.campaign_id}">
{if $campaign_stats}
<div class="table-responsive-wrapper">
    <table width="100%" class="table table-middle table--relative table-responsive">
    <thead>
        <tr>
            <th>{__("title")}</th>
            <th>{__("clicks")}</th>
        </tr>
    </thead>
    <tbody>
    {foreach from=$campaign_stats item="newsletter"}
    <tr>
        <td data-th="{__("title")}">{$newsletter.newsletter}</td>
        <td data-th="{__("clicks")}">{$newsletter.clicks|default:0}</td>
    </tr>
    {/foreach}
    </tbody>
    </table>
</div>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}
<!--content_campaign_stats_{$campaign.campaign_id}--></div>