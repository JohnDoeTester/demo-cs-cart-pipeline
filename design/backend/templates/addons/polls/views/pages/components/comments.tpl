{if $smarty.request.answer_id}
    {assign var="suffix" value="_a_`$smarty.request.answer_id`"}
{elseif $smarty.request.item_id}
    {assign var="suffix" value="_q_`$smarty.request.item_id`"}
{/if}

<div id="content_poll_statistics_comments{$suffix}">
{if $comments}

{include file="common/pagination.tpl" div_id="pagination_comments_`$suffix`"}
<div class="table-responsive-wrapper">
    <table cellpadding="0" cellspacing="0" border="0" width="100%" class="table table-middle table--relative table-responsive">
    <tr>
        <th>{__("date")}</th>
        <th>{__("comment")}</th>
        <th width="100%">&nbsp;</th>
    </tr>
    {foreach from=$comments item="comment"}
    <tr {cycle values="class=\"table-row\","}>
           <td class="nowrap" data-th="{__("date")}">{$comment.time|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</td>
           <td class="nowrap" width="350" data-th="{__("comment")}">{$comment.comment}</td>
           <td width="100%" data-th="&nbsp;">&nbsp;</td>
    </tr>
    {/foreach}
    </table>
</div>
{include file="common/pagination.tpl" div_id="pagination_comments_`$suffix`"}

{/if}
<!--content_poll_statistics_comments{$suffix}--></div>