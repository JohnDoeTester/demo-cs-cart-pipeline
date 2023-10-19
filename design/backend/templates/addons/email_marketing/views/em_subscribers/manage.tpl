{script src="js/tygh/tabs.js"}
{script src="js/lib/bootstrap_switch/js/bootstrapSwitch.js"}

{capture name="sidebar"}

    {include file="common/settings_sidebar.tpl" settings=$em_settings}

    {include file="addons/email_marketing/views/em_subscribers/components/subscribers_search_form.tpl" dispatch="em_subscribers.manage"}
{/capture}

{capture name="mainbox"}

{$has_permission = fn_check_permissions("em_subscribers", "delete", "admin", "POST")}

<form action="{""|fn_url}" method="post" name="subscribers_form">
{include file="common/pagination.tpl" save_current_page=true save_current_url=true}

{if $subscribers}
    {capture name="em_subscribers_table"}
        <div class="table-responsive-wrapper longtap-selection">
            <table width="100%" class="table table-middle table--relative table-responsive">
            <thead
                data-ca-bulkedit-default-object="true"
                data-ca-bulkedit-component="defaultObject"
            >
            <tr>
                <th width="6%">
                    {include file="common/check_items.tpl" is_check_all_shown=true is_check_disabled=!$has_permission} 

                    <input type="checkbox"
                        class="bulkedit-toggler hide"
                        data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]" 
                        data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                    />
                </th>
                <th>{__("email")}</th>
                <th width="20%">{__("name")}</th>
                <th width="20%">{__("registered")}</th>
                <th width="10%">{__("status")}</th>
                <th width="8%">&nbsp;</th>
            </tr>
            </thead>
            {foreach from=$subscribers item="s"}
            <tbody>
            <tr class="cm-longtap-target"
                {if $has_permission}
                    data-ca-longtap-action="setCheckBox"
                    data-ca-longtap-target="input.cm-item"
                    data-ca-id="{$s.subscriber_id}"
                {/if}
            >
                <td width="6%" class="mobile-hide">
                    <input type="checkbox" name="subscriber_ids[]" value="{$s.subscriber_id}" class="cm-item hide" />
                </td>
                <td data-th="{__("email")}">
                    <input type="hidden" name="subscribers[{$s.subscriber_id}][email]" value="{$s.email}" />
                    <a href="mailto:{$s.email|escape:url}">{$s.email}</a>
                </td>
                <td width="20%" data-th="{__("name")}">
                    {$s.name|default:"-"}
                </td>
                <td width="20%" data-th="{__("registered")}">
                    {$s.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
                </td>
                <td width="10%" class="nowrap" data-th="{__("status")}">
                    {if $s.status == "A"}{__("active")}{else}{__("pending")}{/if}
                </td>
                <td width="8%" class="nowrap right" data-th="{__("tools")}">
                    {capture name="tools_list"}
                        <li>{btn type="list" class="cm-confirm" text=__("delete") href="em_subscribers.delete?subscriber_id=`$s.subscriber_id`" method="POST"}</li>
                    {/capture}
                    <div class="hidden-tools">
                        {dropdown content=$smarty.capture.tools_list}
                    </div>
                </td>
            </tr>
            </tbody>
            {/foreach}
            </table>
        </div>
    {/capture}

    {include file="common/context_menu_wrapper.tpl"
        form="subscribers_form"
        object="em_subscribers"
        is_check_all_shown=true
        items=$smarty.capture.em_subscribers_table
        has_permissions=$has_permission
    }
{else}
    <div class="no-items">
        {__("no_data")}
        {if $em_support.import}
        <p>
        {btn type="text_add" text=__("import") href="em_subscribers.import"|fn_url method="POST"}
        </p>
        {/if}
    </div>
{/if}

{include file="common/pagination.tpl"}
</form>

{capture name="add_new_picker"}

    <form action="{""|fn_url}" method="post" name="subscribers_form_0" class="form-horizontal form-edit ">
    <input type="hidden" name="subscriber_id" value="0" />
    <div class="tabs cm-j-tabs">
        <ul class="nav nav-tabs">
            <li id="tab_mailing_list_details_0" class="cm-js active"><a>{__("general")}</a></li>
        </ul>
    </div>

    <div class="cm-tabs-content" id="content_tab_mailing_list_details_0">
    <fieldset>
        <div class="control-group">
            <label for="elm_subscribers_email" class="control-label cm-required cm-email">{__("email")}</label>
            <div class="controls">
            <input type="text" name="subscriber_data[email]" id="elm_subscribers_email" value="" class="span6" />
            </div>
        </div>

        <div class="control-group">
            <label for="elm_subscribers_name" class="control-label">{__("person_name")}</label>
            <div class="controls">
            <input type="text" name="subscriber_data[name]" id="elm_subscribers_name" value="" class="span6" />
            </div>
        </div>

        <div class="control-group">
            <label for="elm_subscribers_status" class="control-label">{__("language")}</label>
            <div class="controls">
                <select name="subscriber_data[lang_code]">
                    {foreach from=""|fn_get_translation_languages item="language"}
                        <option value="{$language.lang_code}" {if $settings.Appearance.frontend_default_language == $language.lang_code}selected="selected"{/if}>{$language.name}</option>
                    {/foreach}
                </select>
            </div>
        </div>

    </fieldset>
    </div>

    <div class="buttons-container">
        {include file="buttons/save_cancel.tpl" but_name="dispatch[em_subscribers.update]" cancel_action="close"}
    </div>

    </form>
{/capture}

{/capture}

{capture name="adv_buttons"}
    {include file="common/popupbox.tpl" id="add_new_subscribers" text=__("email_marketing.new_subscriber") content=$smarty.capture.add_new_picker act="general" icon="icon-plus" title=__("add_subscriber")}
{/capture}

{capture name="buttons"}

        {capture name="tools_list"}
            {if $em_support.manual_sync}
                <li>{btn type="list" text=__("email_marketing.sync") href="em_subscribers.sync" method="POST"}</li>
            {/if}

            {if $subscribers}
                <li>{btn type="list" text=__("email_marketing.export_all") href="exim.delete_range?section=subscribers&pattern_id=em_subscribers"|fn_url method="POST"}</li>
            {/if}
        {/capture}
        {dropdown content=$smarty.capture.tools_list}

        {if $subscribers}
            {include file="buttons/save.tpl" but_name="dispatch[em_subscribers.m_update]" but_role="action" but_target_form="subscribers_form" but_meta="cm-submit"}
        {/if}
{/capture}

{include file="common/mainbox.tpl" title=__("subscribers") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons sidebar=$smarty.capture.sidebar select_languages=false}
