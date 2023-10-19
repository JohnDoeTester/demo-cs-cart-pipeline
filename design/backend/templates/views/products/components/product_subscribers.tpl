<div class="btn-toolbar clearfix">
        <div class="pull-left">
            {include file="views/products/components/search_product_subscribers.tpl" dispatch="products.update" search=$product_subscribers_search}
        </div>
    {if !$runtime.company_id || $runtime.company_id && $product_data.company_id == $runtime.company_id}        
        <div class="pull-right">
            {capture name="new_email_picker"}
                <form action="{"products.update?product_id=`$product_id`&selected_section=subscribers"|fn_url}" method="post" name="subscribers_form_0" class=" ">
                    <div class="form-horizontal form-edit cm-tabs-content" id="content_tab_user_details">
                        <div class="control-group">
                            <label for="users_email" class="control-label cm-required cm-email">{__("email")}</label>
                            <div class="controls">
                                <input type="text" name="add_subscriber[email]" id="users_email" value="" class="span8" />
                                <input type="hidden" name="add_users[0]" id="users_id" value="0"/>
                            </div>
                        </div>
                        {hook name="product_subscribers:new_email_picker"}
                        {/hook}
                    </div>
                <div class="buttons-container">
                    {include file="buttons/save_cancel.tpl" but_name="dispatch[products.update]" cancel_action="close"}
                </div>
                </form>
            {/capture}
            <span class="shift-right">
                {include file="common/popupbox.tpl" id="add_new_subscribers" text=__("new_subscribers") content=$smarty.capture.new_email_picker link_text=__("add_subscriber") act="general" icon="icon-plus"}
            </span>

            {if $is_allow_update_products}
                {include file="pickers/users/picker.tpl" no_container=true data_id="subscr_user" picker_for="subscribers" extra_var="products.update?product_id=`$product_id`&selected_section=subscribers" but_text=__("add_subscribers_from_users") view_mode="button" but_meta="btn"}
            {/if}

        </div>
    {/if}
</div>
<form action="{""|fn_url}" method="post" name="subscribers_form" class="{if ""|fn_check_form_permissions || ($runtime.company_id && $product_data.shared_product == "Y" && $product_data.company_id != $runtime.company_id)} cm-hide-inputs{/if}">
<input type="hidden" name="redirect_url" value="{$config.current_url|fn_link_attach:"selected_section=subscribers"}">
{include file="common/pagination.tpl" save_current_page=true div_id="product_subscribers" search=$product_subscribers_search}

{if $product_subscribers}
    {capture name="products_subscribers_table"}
        <div class="table-responsive-wrapper longtap-selection">
            <table width="100%" class="table table-middle table--relative table-responsive">
            <thead
                    data-ca-bulkedit-default-object="true"
                    data-ca-bulkedit-component="defaultObject"
            >
                <tr>
                {hook name="product_subscribers:manage_head"}
                    <th class="center" width="1%">
                        {include file="common/check_items.tpl"}

                        <input type="checkbox"
                               class="bulkedit-toggler hide"
                               data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                               data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                        />
                    </th>
                    <th width="50%">{__("email")}</th>
                {/hook}
                <th>&nbsp;</th>
            </tr>
            </thead>
            <tbody>
                {foreach $product_subscribers as $s}
                <tr data-ca-longtap-action="setCheckBox"
                    data-ca-longtap-target="input.cm-item"
                    data-ca-id="{$s.subscriber_id}"
                >

                    {hook name="product_subscribers:manage_body"}
                        <td class="center" data-th="">
                               <input type="checkbox" name="subscriber_ids[]" value="{$s.subscriber_id}" class="cm-item hide" /></td>
                        <td data-th="{__("email")}"><input type="hidden" name="subscribers[{$s.subscriber_id}][email]" value="{$s.email}" />
                            <a href="mailto:{$s.email|escape:url}">{$s.email}</a>
                        </td>
                        <input type="hidden" name="product_id" value="{$product_id}" />
                    {/hook}
                    <td class="nowrap right" width="5%" data-th="{__("tools")}">
                        {capture name="tools_list"}
                            <li>{btn type="delete" href="products.update?product_id=`$product_id`&selected_section=subscribers&deleted_subscription_id=`$s.subscriber_id`"}</li>
                        {/capture}
                        <div class="hidden-tools">
                        {if
                            $is_allow_update_products
                            && (
                                !$runtime.company_id
                                || $runtime.company_id
                                && $product_data.company_id == $runtime.company_id
                            )
                        }
                            {dropdown content=$smarty.capture.tools_list}
                        {/if}
                        </div>
                    </td>
                </tr>
                {/foreach}
            </tbody>
            </table>
        </div>
    {/capture}

    {include file="common/context_menu_wrapper.tpl"
        form="manage_products_form"
        object="product_subscriptions"
        items=$smarty.capture.products_subscribers_table
        is_check_all_shown=true
    }
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl" div_id="product_subscribers" search=$product_subscribers_search}

</form>
