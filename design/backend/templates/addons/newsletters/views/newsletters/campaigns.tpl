{capture name="mainbox"}

<form action="{""|fn_url}" method="post" name="update_campaign_form_{$id}" class="" id="update_campaign_form">

{include file="common/pagination.tpl" save_current_page=true save_current_url=true}

{if $campaigns}
    {capture name="campaigns_table"}
        <div class="table-responsive-wrapper longtap-selection">
            <table class="table table-middle table--relative table-responsive" width="100%">
                <thead
                        data-ca-bulkedit-default-object="true"
                        data-ca-bulkedit-component="defaultObject"
                >
                    <tr>
                        <th class="center mobile-hide" width="1%">
                            {include file="common/check_items.tpl"}

                            <input type="checkbox"
                                   class="bulkedit-toggler hide"
                                   data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                                   data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                            />
                        </th>
                        <th width="70%">{__("name")}</th>
                        <th width="5%" class="center">&nbsp;</th>
                        <th width="10%" class="right">{__("status")}</th>
                    </tr>
                </thead>
            <tbody>
                {foreach $campaigns as $c}
                    <tr class="cm-row-status-{$c.status|lower} cm-longtap-target"
                        data-ca-longtap-action="setCheckBox"
                        data-ca-longtap-target="input.cm-item"
                        data-ca-id="{$c.campaign_id}"
                    >
                        <td class="left mobile-hide" width="1%">
                            <input type="checkbox" name="campaign_ids[]" value="{$c.campaign_id}" class="cm-item cm-item-status-{$c.status|lower} hide" /></td>
                        <td data-th="{__("name")}">
                            <input type="text" name="campaigns[{$c.campaign_id}][name]" value="{$c.object}" class="input-large input-hidden" /></td>
                        <td class="nowrap" data-th="{__("tools")}">
                            {capture name="tools_list"}
                                <li>{btn type="dialog" text=__("campaign_stats") title=__("campaign_stats") href="newsletters.campaign_stats?campaign_id=`$c.campaign_id`" target_id="campaign_stats_`$c.campaign_id`"}</li>
                                <li class="divider"></li>
                                <li>{btn type="list" class="cm-confirm" text=__("delete") href="newsletters.delete_campaign?campaign_id=`$c.campaign_id`" method="POST"}</li>
                            {/capture}
                            <div class="hidden-tools">
                                {dropdown content=$smarty.capture.tools_list}
                            </div>
                        </td>
                        <td class="nowrap right" data-th="{__("status")}">
                            {include file="common/select_popup.tpl" id=$c.campaign_id status=$c.status hidden=false object_id_name="campaign_id" table="newsletter_campaigns"}
                        </td>
                    </tr>
                {/foreach}
            </tbody>
            </table>
        </div>
    {/capture}

    {include file="common/context_menu_wrapper.tpl"
        form="update_campaign_form"
        object="campaigns"
        items=$smarty.capture.campaigns_table
    }
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl"}
</form>

{capture name="buttons"}
    {if $campaigns}
        {include file="buttons/save.tpl" but_name="dispatch[newsletters.m_update_campaigns]" but_role="action" but_target_form="update_campaign_form_`$id`" but_meta="cm-submit"}
    {/if}
{/capture}

{capture name="adv_buttons"}
    {if $is_allow_add_campaign}
        {capture name="add_new_picker"}
            <form action="{""|fn_url}" method="post" class="form-horizontal form-edit" name="add_campaign_form">
                <div class="tabs cm-j-tabs">
                    <ul class="nav nav-tabs">
                        <li id="tab_steps_new" class="cm-js active"><a>{__("general")}</a></li>
                    </ul>
                </div>

                <div class="cm-tabs-content" id="content_tab_steps_new">
                    <fieldset>
                        <div class="control-group">
                            <label class="control-label cm-required" for="c_name">{__("name")}</label>
                            <div class="controls">
                                <input class="span9" type="text" id="c_name" name="campaign_data[name]" value="" size="60" />
                            </div>
                        </div>

                        {include file="common/select_status.tpl" input_name="campaign_data[status]" id="c_status"}

                    </fieldset>
                </div>

                <div class="buttons-container">
                    {include file="buttons/save_cancel.tpl" but_name="dispatch[newsletters.add_campaign]" cancel_action="close" text=__("add_campaign")}
                </div>
            </form>
        {/capture}
        {include file="common/popupbox.tpl" id="add_new_campaign" text=__("new_campaign") title=__("add_campaign") act="general" content=$smarty.capture.add_new_picker icon="icon-plus"}
    {/if}
{/capture}

{/capture}
{include file="common/mainbox.tpl" title=__("newsletters") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons select_languages=true}
