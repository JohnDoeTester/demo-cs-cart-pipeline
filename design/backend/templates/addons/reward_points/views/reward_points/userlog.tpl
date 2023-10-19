{script src="js/tygh/tabs.js"}
{capture name="mainbox"}

{** userlog description section **}
{$c_url=$config.current_url|fn_query_remove:"sort_by":"sort_order"}

<form action="{""|fn_url}" method="post" name="userlog_form" class="" enctype="multipart/form-data">
    <input type="hidden" name="user_id" value="{$smarty.request.user_id}">

    {include file="common/pagination.tpl" save_current_url=true}
    {include_ext file="common/icon.tpl" class="icon-`$search.sort_order_rev`" assign=c_icon}
    {include_ext file="common/icon.tpl" class="icon-dummy" assign=c_dummy}

    {if $userlog}
        {capture name="reward_points_table"}
            <div class="table-responsive-wrapper longtap-selection">
                <table class="table table-middle table--relative table-responsive">
                <thead
                    data-ca-bulkedit-default-object="true"
                    data-ca-bulkedit-component="defaultObject"
                >
                    <tr>
                        <th width="6%" class="left mobile-hide">
                            {include file="common/check_items.tpl" is_check_all_shown=true}

                            <input type="checkbox"
                                class="bulkedit-toggler hide"
                                data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                                data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                            />
                        </th>
                        <th width="14%"><a class="cm-ajax{if $search.sort_by === "timestamp"} sort-link-{$search.sort_order_rev}{/if}" href="{"`$c_url`&sort_by=timestamp&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("date")} {if $search.sort_by === "timestamp"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                        <th width="10%"><a class="cm-ajax{if $search.sort_by === "amount"} sort-link-{$search.sort_order_rev}{/if}" href="{"`$c_url`&sort_by=amount&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("points")} {if $search.sort_by === "amount"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                        <th width="60%">{__("reason")}</th>
                        <th width="10%">&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$userlog item="ul"}
                    <tr class="cm-longtap-target"
                        data-ca-longtap-action="setCheckBox"
                        data-ca-longtap-target="input.cm-item"
                        data-ca-id="{$ul.change_id}"
                    >
                        <td width="6%" class="left mobile-hide">
                            <input type="checkbox" name="change_ids[]" value="{$ul.change_id}" class="cm-item hide">
                        </td>
                        <td width="14%" data-th="{__("date")}">{$ul.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</td>
                        <td width="10%" data-th="{__("points")}">{$ul.amount}</td>
                        <td width="60%" data-th="{__("reason")}">
                            {if $ul.action == $smarty.const.CHANGE_DUE_ORDER}
                                {assign var="statuses" value=$smarty.const.STATUSES_ORDER|fn_get_simple_statuses:true:true}
                                {assign var="reason" value=$ul.reason|unserialize}
                                {assign var="order_exist" value=$reason.order_id|fn_get_order_name}

                                {__("order")}&nbsp;{if $order_exist}<a href="{"orders.details?order_id=`$reason.order_id`"|fn_url}" class="underlined">{/if}<span>#{$reason.order_id}</span>{if $order_exist}</a>{/if}:&nbsp;{$statuses[$reason.from]}&nbsp;&#8212;&#8250;&nbsp;{$statuses[$reason.to]}{if $reason.text}&nbsp;({__($reason.text)}){/if}

                            {elseif $ul.action == $smarty.const.CHANGE_DUE_USE}
                                {assign var="order_exist" value=$ul.reason|fn_get_order_name}
                                {__("text_points_used_in_order")}: {if $order_exist}<a href="{"orders.details?order_id=`$ul.reason`"|fn_url}">{/if}<span>#{$ul.reason}</span>{if $order_exist}</a>{/if}
                            {elseif $ul.action == $smarty.const.CHANGE_DUE_ORDER_DELETE}
                                {assign var="reason" value=$ul.reason|unserialize}
                                {__("order")} <span>#{$reason.order_id}</span>: {__("deleted")}
                            {elseif $ul.action == $smarty.const.CHANGE_DUE_ORDER_PLACE}
                                {assign var="reason" value=$ul.reason|unserialize}
                                {assign var="order_exist" value=$reason.order_id|fn_get_order_name}
                                {__("order")} {if $order_exist}<a href="{"orders.details?order_id=`$reason.order_id`"|fn_url}" class="underlined">{/if}<span>#{$reason.order_id}</span>{if $order_exist}</a>{/if}: {__("order_placed")}
                            {else}
                                {hook name="reward_points:userlog"}
                                    {$ul.reason}
                                {/hook}
                            {/if}
                        </td>
                        <td width="10%" class="nowrap right" data-th="{__("tools")}">
                            <div class="hidden-tools">
                                {capture name="tools_list"}
                                    <li>{btn type="delete" href="reward_points.delete?user_id=`$smarty.request.user_id`&change_id=`$ul.change_id`" class="cm-confirm" method="POST"}</li>
                                {/capture}
                                {dropdown content=$smarty.capture.tools_list}
                            </div>
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
                </table>
            </div>
        {/capture}

        {include file="common/context_menu_wrapper.tpl"
            form="userlog_form"
            object="reward_points"
            items=$smarty.capture.reward_points_table
            is_check_all_shown=true
        }
    {else}
        <p class="no-items">{__("no_data")}</p>
    {/if}

{include file="common/pagination.tpl"}

</form>
{** / userlog description section **}


{capture name="sidebar"}
    <div class="sidebar-row">
    <h6>{__("log")}</h6>
    <ul class="unstyled">
        <li><strong>{__("customer")}:</strong>  <a href="{"profiles.update?user_id=`$user.user_id`"|fn_url}">{$user.firstname} {$user.lastname}</a></li>
        <li><strong>{__("points")}:</strong> {if $user.points}{$user.points}{else}0{/if}</li>
    </ul>
    </div>
{/capture}

{** Change points section **}

{capture name="buttons"}
    {if $userlog}
        {capture name="tools_list"}
            <li>{btn type="delete" text=__("cleanup_log") class="cm-submit" dispatch="dispatch[reward_points.cleanup_logs]" form="userlog_form"}</li>
        {/capture}
        {dropdown content=$smarty.capture.tools_list class="mobile-hide"}
    {/if}
{/capture}

{capture name="adv_buttons"}
    {capture name="add_new_picker"}
        <form action="{""|fn_url}" method="post" name="reward_points_form" enctype="multipart/form-data" class="">
            <input type="hidden" name="user_id" value="{$smarty.request.user_id}">
            <input type="hidden" name="redirect_url" value="{$config.current_url}">

            <div class="tabs cm-j-tabs">
                <ul class="nav nav-tabs">
                    <li id="tab_general" class="cm-js active"><a>{__("general")}</a></li>
                </ul>
            </div>

            <div class="cm-tabs-content" id="content_tab_general">
                <fieldset class="form-horizontal">
                    <div class="control-group">
                        <label class="control-label">{__("action")}:</label>
                        <div class="controls">
                            <label for="reason_action_A" class="radio inline">
                                <input type="radio" name="reason[action]" id="reason_action_A" value="A" checked="checked">
                                {__("add")}
                            </label>
                            <label for="reason_action_S" class="radio inline">
                                <input type="radio" name="reason[action]" id="reason_action_S" value="S">
                                {__("subtract")}
                            </label>
                        </div>
                    </div>

                    <div class="control-group">
                        <label for="reason_amount" class="cm-required control-label">{__("value")}:</label>
                        <div class="controls">
                            <input type="text" value="" name="reason[amount]" id="reason_amount" class="input-text" size="5" />
                        </div>
                    </div>

                    <div class="control-group">
                        <label  class="control-label" for="reason_reason">{__("reason")}:</label>
                        <div class="controls">
                            <textarea name="reason[reason]" id="reason_reason" cols="55" rows="8" class="input-textarea-long"></textarea>
                        </div>
                    </div>

                    <div class="control-group">
                        <label  class="control-label" for="notify_user">{__("notify_customer")}:</label>
                        <div class="controls">
                            <input type="checkbox" name="notify_user" value="Y" checked="checked" id="notify_user" />
                        </div>
                    </div>
                </fieldset>
            </div>

            <div class="buttons-container">
                {include file="buttons/save_cancel.tpl" but_name="dispatch[reward_points.change_points]" cancel_action="close" but_text=__("change")}
            </div>
        </form>
    {/capture}
    {include file="common/popupbox.tpl" id="change_points" text=__("change_points") content=$smarty.capture.add_new_picker title=__("add_subtract_points") act="general" icon="icon-plus"}
{/capture}

{** /Change points section **}
{/capture}
{include file="common/mainbox.tpl" title=__("reward_points_log") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar adv_buttons=$smarty.capture.adv_buttons}
