{capture name="mainbox"}
{include file="common/pagination.tpl" save_current_page=true save_current_url=true}

{$c_url = $config.current_url|fn_query_remove:"sort_by":"sort_order"}
{include_ext file="common/icon.tpl" class="icon-`$search.sort_order_rev`" assign=c_icon}
{include_ext file="common/icon.tpl" class="icon-dummy" assign=c_dummy}
{if $invitations}
    <form action="{""|fn_url}" method="post" name="invited_vendors_form" id="invited_vendors_form">
        {capture name="companies_invitations_table"}
            <div class="table-responsive-wrapper longtap-selection">
                <table class="table table-middle table--relative table-responsive">
                    <thead
                            data-ca-bulkedit-default-object="true"
                            data-ca-bulkedit-component="defaultObject"
                    >
                        <tr>
                            <th class="mobile-hide" width="1%">
                                {include file="common/check_items.tpl"}

                                <input type="checkbox"
                                       class="bulkedit-toggler hide"
                                       data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                                       data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                                />
                            </th>
                            <th width="69%" class="nowrap">
                                <a class="cm-ajax" href="{"`$c_url`&sort_by=email&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("email")}{if $search.sort_by === "email"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                            <th width="20%" class="nowrap">
                                <a class="cm-ajax" href="{"`$c_url`&sort_by=invited_at&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("vendor_invited_at")}{if $search.sort_by === "invited_at"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                            <th width="10%" class="mobile-hide">&nbsp;</th>
                        </tr>
                    </thead>

                    {foreach $invitations as $invitation}
                        <tr  class="cm-row-item cm-longtap-target"
                             data-ca-longtap-action="setCheckBox"
                             data-ca-longtap-target="input.cm-item"
                             data-ca-id="{$invitation.invitation_key}"
                        >
                            <td class="mobile-hide" width="1%">
                                <input name="invitation_keys[]" type="checkbox" value="{$invitation.invitation_key}" class="cm-item hide" /></td>
                            <td width="69%" data-th="{__("email")}">
                                {$invitation.email}
                            <td width="20%" data-th="{__("vendor_invited_at")}">
                                {$invitation.invited_at|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
                            </td>
                            <td width="10%" class="right mobile-hide">
                                <div class="hidden-tools">
                                    {capture name="tools_list"}
                                        <li>{btn type="list" text=__("delete") class="cm-confirm" href="companies.delete_invitation?invitation_key=`$invitation.invitation_key`" method="POST"}</li>
                                    {/capture}
                                    {dropdown content=$smarty.capture.tools_list}
                                </div>
                            </td>
                        </tr>
                    {/foreach}
                </table>
            </div>
        {/capture}

        {include file="common/context_menu_wrapper.tpl"
            form="invited_vendors_form"
            object="companies_invitations"
            items=$smarty.capture.companies_invitations_table
            is_check_all_shown=true
        }
    </form>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl"}

{capture name="buttons"}
    {hook name="companies:manage_invite_vendors"}
        {include
            file="buttons/button.tpl"
            but_role="text"
            but_href="companies.invite"
            title=__("invite_vendors_title")
            but_text=__("invite_vendors")
            but_meta="btn cm-dialog-opener"
        }
    {/hook}
{/capture}

{/capture}
{include file="common/mainbox.tpl" title=__("pending_vendor_invitations") content=$smarty.capture.mainbox tools=$smarty.capture.tools buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons}
