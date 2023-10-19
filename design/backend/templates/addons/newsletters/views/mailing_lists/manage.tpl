{capture name="mainbox"}
<form action="{""|fn_url}" method="post" name="mailing_lists_form" id="mailing_lists_form">
    <div class="items-container" id="mailing_lists">
    {if $mailing_lists}
        {capture name="mailing_lists_table"}
            <div class="table-responsive-wrapper longtap-selection">
                <table width="100%" class="table table-middle table--relative table-responsive table-responsive-w-titles">
                    <thead
                            data-ca-bulkedit-default-object="true"
                            data-ca-bulkedit-component="defaultObject"
                    >
                        <tr>
                            <th>
                                {include file="common/check_items.tpl"}

                                <input type="checkbox"
                                       class="bulkedit-toggler hide"
                                       data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                                       data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                                />
                            </th>
                            <th>{__("name")}</th>
                            <th>{__("subscribers_num")}</th>
                            <th width="5%">&nbsp;</th>
                            <th width="15%" class="right">{__("status")}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $mailing_lists as $mailing_list}

                            {capture name="tool_items"}
                                <li>{btn type="list" text=__("manage_subscribers") href="subscribers.manage?list_id=`$mailing_list.list_id`"}</li>
                                <li class="divider"></li>
                            {/capture}

                            {include file="common/object_group.tpl"
                                no_table=true
                                id=$mailing_list.list_id
                                text=$mailing_list.object
                                status=$mailing_list.status
                                hidden=true
                                href="mailing_lists.update?list_id=`$mailing_list.list_id`"
                                details="{__("subscribers_num")}: `$mailing_list.subscribers_num`"
                                object_id_name="list_id"
                                table="mailing_lists"
                                href_delete="mailing_lists.delete?list_id=`$mailing_list.list_id`"
                                delete_target_id="mailing_lists"
                                header_text=$mailing_list.object
                                tool_items=$smarty.capture.tool_items
                                is_bulkedit_menu=true
                                checkbox_col_width="6%"
                                checkbox_name="list_ids[]"
                                show_checkboxes=true
                                hidden_checkbox=true
                                no_padding=true
                            }

                        {/foreach}
                    </tbody>
                </table>
            </div>
        {/capture}

        {include file="common/context_menu_wrapper.tpl"
            form="mailing_lists_form"
            object="mailing_lists"
            items=$smarty.capture.mailing_lists_table
        }
    {else}
        <p class="no-items">{__("no_data")}</p>
    {/if}
    <!--mailing_lists--></div>
</form>

    {capture name="adv_buttons"}
        {if $is_allow_update_mailing_lists}
            {capture name="add_new_picker"}
                {include file="addons/newsletters/views/mailing_lists/update.tpl" mailing_list=[]}
            {/capture}
            {include file="common/popupbox.tpl" id="add_new_mailing_lists" text=__("new_mailing_lists") content=$smarty.capture.add_new_picker title=__("add_mailing_lists") act="general" icon="icon-plus"}
        {/if}
    {/capture}
{/capture}

{include file="common/mainbox.tpl" title=__("mailing_lists") content=$smarty.capture.mainbox adv_buttons=$smarty.capture.adv_buttons select_languages=true}
