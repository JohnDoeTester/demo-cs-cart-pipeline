{script src="js/tygh/tabs.js"}
{script src="js/tygh/backend/product_options_manage.js"}
{if $enable_search}
    {script src="js/tygh/backend/products/products_update_options.js"}
{/if}
{literal}
    <script>
    function fn_check_option_type(value, tag_id)
    {
        var id = tag_id.replace('option_type_', '').replace('elm_', '');
        var tabLink = Tygh.$('#tab_option_variants_' + id + ' a');

        Tygh.$('#tab_option_variants_' + id).toggleBy(!(value == 'S' || value == 'R' || value == 'C'));
        Tygh.$('#required_options_' + id).toggleBy(!(value == 'I' || value == 'T' || value == 'F'));
        Tygh.$('#extra_options_' + id).toggleBy(!(value == 'I' || value == 'T'));
        Tygh.$('#file_options_' + id).toggleBy(!(value == 'F'));

        value === 'C'
            ? tabLink.text(tabLink.data('caModifiersText'))
            : tabLink.text(tabLink.data('caVariantsText'));

        if (value === 'C') {
            var t = Tygh.$('table', '#content_tab_option_variants_' + id);
            Tygh.$('.cm-non-cb', t).switchAvailability(true); // hide obsolete columns
            Tygh.$('tbody:gt(1)', t).switchAvailability(true); // hide obsolete rows

        } else if (value === 'S' || value === 'R') {
            var t = Tygh.$('table', '#content_tab_option_variants_' + id);
            Tygh.$('.cm-non-cb', t).switchAvailability(false); // show all columns
            Tygh.$('tbody', t).switchAvailability(false); // show all rows
            Tygh.$('#box_add_variant_' + id).show(); // show "add new variants" box

        } else if (value === 'I' || value === 'T') {
            Tygh.$('#extra_options_' + id).show(); // show "add new variants" box
        }
    }
    </script>
{/literal}

{$c_url = $config.current_url|fn_query_remove:"sort_by":"sort_order"}
{$allow_add_option = fn_check_permissions("product_options", "quick_add", "admin", "POST")}
{$is_global = $object === "global"}
{include_ext file="common/icon.tpl" class="icon-`$search.sort_order_rev`" assign=c_icon}

{capture name="mainbox"}

    {if $is_global}
        {$select_languages = true}
        {$delete_target_id = "pagination_contents"}
        {$show_checkboxes = true}
    {else}
        {$delete_target_id = "product_options_list"}
        {$show_checkboxes = false}
    {/if}

    {include file="common/pagination.tpl"}

    {if !($runtime.company_id && ((fn_allowed_for("MULTIVENDOR") && !$is_global) || $product_data.shared_product == "Y") && $runtime.company_id != $product_data.company_id)}
        {capture name="toolbar"}
            <div class="control-toolbar__btns-center">
                {capture name="add_new_picker"}
                    {if $product_data}
                        {include file="views/product_options/update.tpl" option_id="0" company_id=$product_data.company_id disable_company_picker=true}
                    {else}
                        {include file="views/product_options/update.tpl" option_id="0"}
                    {/if}
                {/capture}
                {if $object == "product"}
                    {$position = "pull-right"}
                {/if}
                {if $view_mode == "embed" && $enable_search}
                    {$enable_add = $enable_add|default:true}

                    {if $object == "product" && "products.update"|fn_check_view_permissions}
                        {include file="views/product_options/components/picker/picker.tpl"
                            input_id="option_add"
                            input_name="product_data[linked_option_ids][]"
                            multiple=true
                            meta="control-toolbar__select"
                            select_class="cm-object-product-options-add"
                            autofocus=$autofocus
                            empty_variant_text=__("create_or_link_an_existing_option")
                            allow_add=$enable_add && $allow_add_option
                            create_option_to_end="true"
                            form="form"
                        }
                    {/if}

                {elseif $view_mode == "embed" && !$enable_search && $allow_add_option}
                    {include file="common/popupbox.tpl" id="add_new_option" text=__("new_option") link_text=__("add_option") act="general" content=$smarty.capture.add_new_picker meta=$position icon="icon-plus"}

                {elseif $allow_add_option}
                    {include file="common/popupbox.tpl" id="add_new_option" text=__("new_option") title=__("add_option") act="general" content=$smarty.capture.add_new_picker meta=$position icon="icon-plus"}
                {/if}

            {$extra nofilter}
        </div>
        {/capture}
    {/if}
        {if $object != "global" && $allow_add_option}
            <div class="control-toolbar cm-toggle-button">
                <div class="control-toolbar__btns">
                    {$smarty.capture.toolbar nofilter}
                </div>
                <div class="control-toolbar__panel">
                    <div id="product_options_quick_add_option"
                        data-ca-product-id="{$product_id}"
                        data-ca-target-id="product_options_list"
                        data-ca-inline-dialog-action-context="products_update_options"
                        data-ca-inline-dialog-url="{"product_options.quick_add"|fn_url}">
                    </div>
                </div>
            </div>
        {else}
            {capture name="adv_buttons"}
                {$smarty.capture.toolbar nofilter}
            {/capture}
        {/if}

        {$product_option_statuses = ""|fn_get_default_statuses:false}
        {$has_permissions = fn_check_permissions("product_options", "update", "admin", "POST")}
        {$has_available_options = empty($runtime.company_id) || in_array($runtime.company_id, array_column($product_options, 'company_id'))}

        <form action="{""|fn_url}" method="post" name="manage_product_options_form" id="manage_product_options_form">
            <input type="hidden" name="return_url" value="{$config.current_url}">

            {capture name="product_options_table"}
                <div class="items-container {if ""|fn_check_form_permissions} cm-hide-inputs{/if}" id="product_options_list">
                    {if $product_options}
                        <div class="table-responsive-wrapper longtap-selection">
                            <table width="100%" class="table table-middle table--relative table-objects table-responsive table--overflow-hidden">
                                {if $is_global}
                                    <thead
                                            data-ca-bulkedit-default-object="true"
                                            data-ca-bulkedit-component="defaultObject"
                                        >
                                        <tr>
                                            <th class="left mobile-hide table__check-items-column">
                                                {include file="common/check_items.tpl"
                                                    check_statuses=($has_permissions) ? ($product_option_statuses) : ""
                                                    is_check_disabled=!$has_available_options
                                                    meta="table__check-items"
                                                }

                                                <input type="checkbox"
                                                    class="bulkedit-toggler hide"
                                                    data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                                                    data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                                                />
                                            </th>
                                            <th>
                                                <div class="th-text-overflow-wrapper">
                                                    {include file="common/table_col_head.tpl" type="internal_option_name" text=__("name")}
                                                    {include file="common/tooltip.tpl" tooltip=__("internal_option_name_tooltip")}
                                                    {include file="common/table_col_head.tpl" type="option_name" text=__("storefront_name")}
                                                </div>
                                            </th>
                                            <th width="10%"></th>
                                            <th width="10%"></th>
                                            <th width="20%" class="right">
                                                {include file="common/table_col_head.tpl" type="status"}
                                            </th>
                                        </tr>
                                    </thead>
                                {/if}
                                <tbody>
                                    {foreach $product_options as $product_option}
                                        {if $object == "product" && $product_option.product_id}
                                            {$details = "({__("individual")})"}
                                            {$query_product_id = ""}
                                        {else}
                                            {$details = ""}
                                            {$query_product_id = "&product_id=`$product_id`"}
                                        {/if}

                                        {if $object == "product"}
                                            {if !$product_option.product_id}
                                                {$query_product_id = "&object=`$object`"}
                                            {else}
                                                {$query_product_id = "&product_id=`$product_id`&object=`$object`"}
                                            {/if}
                                            {$query_delete_product_id = "&product_id=`$product_id`"}
                                            {$allow_save = $product_data|fn_allow_save_object:"products"}
                                        {else}
                                            {$query_product_id = ""}
                                            {$query_delete_product_id = ""}
                                            {$allow_save = $product_option|fn_allow_save_object:"product_options"}
                                        {/if}

                                        {if "MULTIVENDOR"|fn_allowed_for}
                                            {if $allow_save && ($product_option.company_id || !$runtime.company_id)}
                                                {$link_text = __("edit")}
                                                {$additional_class = "cm-no-hide-input cm-longtap-target {if $product_option.company_id && !$runtime.company_id}cm-vendor-product-option{/if}"}
                                                {$hide_for_vendor = false}
                                            {else}
                                                {$link_text = __("view")}
                                                {$additional_class = "cm-longtap-target {if $product_option.company_id && !$runtime.company_id}cm-vendor-product-option{/if}"}
                                                {$hide_for_vendor = true}
                                            {/if}
                                        {/if}

                                        {$status = $product_option.status}
                                        {$href_delete = "product_options.delete?option_id=`$product_option.option_id``$query_delete_product_id`"}

                                        {if "ULTIMATE"|fn_allowed_for}
                                            {$non_editable = false}
                                            {if $runtime.company_id && (($product_data.shared_product == "Y" && $runtime.company_id != $product_data.company_id) || ($object == "global" && $runtime.company_id != $product_option.company_id))}
                                                {$link_text = __("view")}
                                                {$href_delete = false}
                                                {$non_editable = true}
                                                {$is_view_link = true}
                                            {/if}
                                        {/if}

                                        {$option_name = $product_option.option_name}

                                        {include file="common/object_group.tpl"
                                            no_table=true
                                            no_padding=true
                                            id=$product_option.option_id
                                            id_prefix="_product_option_"
                                            details=$details
                                            text=$product_option.internal_option_name
                                            href_desc="<br />{$product_option.option_name}"
                                            href_col_width="70%"
                                            hide_for_vendor=$hide_for_vendor
                                            status=$status
                                            table="product_options"
                                            object_id_name="option_id"
                                            href="product_options.update?option_id=`$product_option.option_id``$query_product_id`"
                                            href_delete=$href_delete
                                            delete_target_id=$delete_target_id
                                            header_text=$product_option.option_name
                                            skip_delete=!$allow_save
                                            additional_class=$additional_class
                                            prefix="product_options"
                                            link_text=$link_text
                                            non_editable=$non_editable
                                            company_object=$product_option
                                            href_desc_row_hint="{__("storefront_name")} / {__("name")}"
                                            status_row_hint="{__("status")}"
                                            checkbox_name="option_ids[]"
                                            show_checkboxes=$show_checkboxes
                                            hidden_checkbox=true
                                            checkbox_col_width="0"
                                            checkbox_col_class="table__check-items-cell"
                                            last_col_width="20%"
                                            is_bulkedit_menu=($is_global && $has_permissions)
                                            bulkedit_disabled_notice=($non_editable) ? "{__("product_options_are_not_selectable_for_context_menu")}" : ""
                                            link_meta="bulkedit-deselect"
                                        }
                                    {/foreach}
                                </tbody>
                            </table>
                        </div>
                    {else}
                        <p class="no-items">{__("no_data")}</p>
                    {/if}
                <!--product_options_list--></div>
            {/capture}

            {include file="common/context_menu_wrapper.tpl"
                form="manage_product_options_form"
                object="product_options"
                items=$smarty.capture.product_options_table
                has_permissions=$is_global && $has_permissions
            }
        </form>
    {include file="common/pagination.tpl"}

{/capture}

{if $object == "product"}
    {$smarty.capture.mainbox nofilter}
{else}
    {include file="common/mainbox.tpl" title=__("options") content=$smarty.capture.mainbox adv_buttons=$smarty.capture.adv_buttons select_language=$select_language}
{/if}
