{if $newsletter_type ==  $smarty.const.NEWSLETTER_TYPE_NEWSLETTER}
    {assign var="object_names" value=__("newsletters")}
{elseif $newsletter_type ==  $smarty.const.NEWSLETTER_TYPE_TEMPLATE}
    {assign var="object_names" value=__("newsletter_templates")}
{elseif $newsletter_type ==  $smarty.const.NEWSLETTER_TYPE_AUTORESPONDER}
    {assign var="object_names" value=__("newsletter_autoresponders")}
{/if}

{capture name="mainbox"}

{include_ext file="common/icon.tpl" class="icon-`$search.sort_order_rev`" assign=c_icon}
{include_ext file="common/icon.tpl" class="icon-dummy" assign=c_dummy}

{$c_url=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
{$rev=$smarty.request.content_id|default:"pagination_contents"}

<form action="{""|fn_url}" method="post" name="newsletters_form" enctype="multipart/form-data" id="newsletters_form">
<input type="hidden" name="fake" value="1" />
<input type="hidden" name="newsletter_type" value="{$newsletter_type}" />

{include file="common/pagination.tpl" save_current_page=true save_current_url=true}
<input type="hidden" name="redirect_url" value="{$config.current_url}" />

{if $newsletters}
    {capture name="newsletters_table"}
        <div class="table-responsive-wrapper longtap-selection">
            <table width="100%" class="table table-middle table-responsive">
            <thead
                    data-ca-bulkedit-default-object="true"
                    data-ca-bulkedit-component="defaultObject"
            >
            <tr>
                <th width="1%">
                    {include file="common/check_items.tpl"}

                    <input type="checkbox"
                           class="bulkedit-toggler hide"
                           data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                           data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                    />
                </th>
                <th width="40%"><a class="cm-ajax" href="{"`$c_url`&sort_by=subject&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("subject")}{if $search.sort_by === "subject"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</th>
                {if $newsletter_type === $smarty.const.NEWSLETTER_TYPE_NEWSLETTER}
                    <th><a class="cm-ajax" href="{"`$c_url`&sort_by=mailing_lists&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("mailing_lists")}{if $search.sort_by === "mailing_lists"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</th>
                    <th><a class="cm-ajax" href="{"`$c_url`&sort_by=date&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("date")}{if $search.sort_by === "date"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</th>
                {/if}
                <th>&nbsp;</th>
                <th class="right"><a class="cm-ajax" href="{"`$c_url`&sort_by=status&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("status")}{if $search.sort_by === "status"}{$c_icon nofilter}{/if}</th>
            </tr>
            </thead>
                <tbody>
                    {foreach $newsletters as $newsletter}
                        <tr class="cm-row-status-{$newsletter.status|lower} cm-longtap-target"
                            data-ca-longtap-action="setCheckBox"
                            data-ca-longtap-target="input.cm-item"
                            data-ca-id="{$newsletter.newsletter_id}"
                        >
                            <td class="left mobile-hide">
                                <input type="checkbox" name="newsletter_ids[]" value="{$newsletter.newsletter_id}" class="cm-item cm-item-status-{$newsletter.status|lower} hide" />
                            </td>
                            <td data-th="{__("subject")}">
                                <a class="row-status" href="{"newsletters.update?newsletter_id=`$newsletter.newsletter_id`"|fn_url}">{$newsletter.newsletter}</a>
                            </td>
                            {if $newsletter_type == $smarty.const.NEWSLETTER_TYPE_NEWSLETTER}
                                <td data-th="{__("mailing_lists")}">
                                    {$newsletter.mailing_list_names|default:" - "}
                                </td>
                                <td data-th="{__("date")}" class="nowrap">
                                    {if $newsletter.sent_date}
                                        {$newsletter.sent_date|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
                                    {else}
                                    &nbsp;-&nbsp;
                                    {/if}
                                </td>
                            {/if}

                            <td class="nowrap right">
                                {capture name="tools_list"}
                                    <li>{btn type="list" text=__("edit") href="newsletters.update?newsletter_id=`$newsletter.newsletter_id`"}</li>
                                    <li>{btn type="list" class="cm-confirm" text=__("delete") href="newsletters.delete?newsletter_id=`$newsletter.newsletter_id`" method="POST"}</li>
                                {/capture}
                                <div class="hidden-tools">
                                    {dropdown content=$smarty.capture.tools_list}
                                </div>
                            </td>
                            <td data-th="{__("status")}" class="right" width="10%">
                                {include file="common/select_popup.tpl" id=$newsletter.newsletter_id status=$newsletter.status items_status="newsletters"|fn_get_predefined_statuses object_id_name="newsletter_id" table="newsletters" popup_additional_class="dropleft"}
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    {/capture}

    {include file="common/context_menu_wrapper.tpl"
        form="newsletters_form"
        object="newsletters"
        items=$smarty.capture.newsletters_table
    }
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl"}
</form>
{/capture}

{capture name="adv_buttons"}
    {if $newsletter_type ==  $smarty.const.NEWSLETTER_TYPE_NEWSLETTER}
        {include file="common/tools.tpl" tool_href="newsletters.add?type=`$smarty.const.NEWSLETTER_TYPE_NEWSLETTER`" prefix="top" hide_tools="true" title=__("add_newsletter")}
    {elseif $newsletter_type ==  $smarty.const.NEWSLETTER_TYPE_TEMPLATE}
        {include file="common/tools.tpl" tool_href="newsletters.add?type=`$smarty.const.NEWSLETTER_TYPE_TEMPLATE`" prefix="top" hide_tools="true" title=__("add_template")}
    {elseif $newsletter_type ==  $smarty.const.NEWSLETTER_TYPE_AUTORESPONDER}
        {include file="common/tools.tpl" tool_href="newsletters.add?type=`$smarty.const.NEWSLETTER_TYPE_AUTORESPONDER`" prefix="top" hide_tools="true" title=__("add_autoresponder")}
    {/if}
{/capture}

{include file="common/mainbox.tpl"
    title=$object_names
    content=$smarty.capture.mainbox
    select_languages=true
    adv_buttons=$smarty.capture.adv_buttons
}