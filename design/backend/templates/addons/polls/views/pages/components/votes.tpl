{if $smarty.request.answer_id}
    {assign var="suffix" value="a_`$smarty.request.answer_id`"}
{elseif $smarty.request.item_id}
    {assign var="suffix" value="q_`$smarty.request.item_id`"}
{elseif $smarty.request.completed == "Y"}
    {assign var="suffix" value="completed"}
{else}
    {assign var="suffix" value="total"}
{/if}

<div id="content_poll_statistics_votes_{$suffix}">

{include file="common/pagination.tpl" div_id="pagination_contents_`$suffix`"}
{if $votes}
<div class="table-responsive-wrapper">
  <table class="table table-middle table--relative table-responsive">
  <thead>
    <tr>
        <th>{__("date")}</th>
        <th>{__("user")}</th>
        <th>{__("ip")}</th>
        <th>{__("completed")}</th>
        <th>&nbsp;</th>
    </tr>
  </thead>
  <tbody>
  {foreach from=$votes item="vote"}
  <tr class="cm-row-item">
         <td class="nowrap" data-th="{__("date")}">{$vote.time|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</td>
         <td data-th="{__("user")}">{if $vote.user_id}{$vote.lastname}{if $vote.lastname && $vote.firstname}&nbsp;{/if}{$vote.firstname}{else}{__("anonymous")}{/if}</td>
         <td data-th="{__("ip")}">{$vote.ip_address}</td>
         <td data-th="{__("completed")}">{if $vote.type == "C"}{__("yes")}{else}{__("no")}{/if}</td>
         <td data-th="&nbsp;">{include file="buttons/clone_delete.tpl" href_delete="pages.delete_vote?vote_id=`$vote.vote_id`" microformats="cm-post"}</td>
  </tr>
  {/foreach}
  </tbody>

  </table>
</div>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}
{include file="common/pagination.tpl" div_id="pagination_contents_`$suffix`"}

<!--content_poll_statistics_votes_{$suffix}--></div>