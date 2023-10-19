{$is_form_readonly = ($product.product_id && $runtime.company_id && (fn_allowed_for("MULTIVENDOR") || $product.shared_product == "Y") && $product.company_id != $runtime.company_id)}

{if $is_form_readonly}
    {$hide_inputs_if_shared_product = "cm-hide-inputs"}
    {$no_hide_input_if_shared_product = "cm-no-hide-input"}
{else}
    {$hide_inputs_if_shared_product = ""}
    {$no_hide_input_if_shared_product = ""}
{/if}

{$redirect_url="products.update?product_id={$product_id}&selected_section=variations"}

<div id="content_variations">
    <form action="{""|fn_url}" method="post" name="manage_variation_products_form" data-ca-main-content-selector="[data-ca-main-content]" class="js-manage-variation-products-form" id="manage_variation_products_form">
        <input type="hidden" value="{$redirect_url|fn_url}" name="redirect_url" class="{$no_hide_input_if_shared_product}">
        <input type="hidden" value="{$product_id}" name="from_product_id">

        <div class="product-variations__toolbar">
            <div class="product-variations__toolbar-left">
                {if $group}
                    {include file="addons/product_variations/views/product_variations/components/group_code.tpl" group=$group}
                {elseif !$is_form_readonly}
                    {include file="addons/product_variations/views/product_variations/components/link_to_group.tpl"}
                {/if}
            </div>
            <div class="product-variations__toolbar-right cm-hide-with-inputs">
                {if $group}
                    {capture name="tools_list"}
                        {if $group}
                            <li>{btn type="list" id="manage_variations" text=__("product_variations.manage") href="products.manage?variation_group_id={$group->getId()}&is_search=Y"}</li>
                            <li>{btn type="list" id="edit_variations_features" text=__("product_variations.edit_features") href="product_features.manage?{http_build_query(["feature_id" => $selected_features|array_keys])}"}</li>

                            {if !$is_form_readonly}
                                <li>{btn type="list" id="delete_variations" class="cm-confirm" text=__("product_variations.delete") href="product_variations.delete?product_id=`$product_id`" method="POST"}</li>
                            {/if}
                        {/if}
                    {/capture}
                    {dropdown content=$smarty.capture.tools_list icon=" " text=__("actions")}
                {/if}
                {if !$is_form_readonly}
                    {include file="common/popupbox.tpl"
                        id="update_product_group"
                        text=__("product_variations.add_variations")
                        href="product_variations.create_variations?product_id=`$product_id`"
                        link_text=__("product_variations.add_variations")
                        link_class="cm-dialog-destroy-on-close"
                        act="general"
                        icon="icon-plus"
                        meta="shift-left"
                    }
                {/if}
            </div>
        </div>

        <div class="product-variations__notification hidden" data-ca-product-variations="beforeEditNotification">
            <div class="alert alert-warning">
                <strong>{__("warning")}</strong>
                {__("product_variations.warning.before_edit_variations_save_product")}
            </div>
        </div>

        {if $products}
            {$context_menu_id = "context_menu_{uniqid()}"}
            {capture name="manage_variation_products_table"}
                <div class="product-variations__container table-responsive-wrapper longtap-selection">
                    <table width="100%" class="table table-middle table--relative table-responsive product-variations__table" data-ca-main-content>
                        <thead
                                data-ca-bulkedit-default-object="true"
                                data-ca-bulkedit-component="defaultObject"
                        >
                        <tr>
                            <th width="1%">
                                {include file="common/check_items.tpl"
                                    class="cm-no-hide-input"
                                    check_statuses=''|fn_get_default_status_filters:true
                                    elms_container="#`$context_menu_id`"
                                }

                                <input type="checkbox"
                                       class="bulkedit-toggler hide"
                                       data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                                       data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                                />
                            </th>
                            <th width="40">&nbsp;</th>
                            <th width="70" class="product-variations__th-img">&nbsp;</th>
                            <th width="30%" class="nowrap"><span>{__("name")}</span></th>
                            <th width="15%" class="nowrap">{__("sku")}</th>
                            {foreach $selected_features as $feature}
                                <th><span>{$feature.internal_name}</span></th>
                            {/foreach}
                            <th width="10%" class="nowrap">{__("price")} ({$currencies.$primary_currency.symbol nofilter})</th>
                            <th width="10%" class="nowrap">{__("quantity")}</th>
                            <th width="60" class="mobile-hide">&nbsp;</th>
                            <th width="9%" class="right"></th>
                        </tr>
                        </thead>
                        {foreach $products as $product}
                            {if !$product.parent_product_id}
                                {if !$product@first}
                                    </tbody>
                                {/if}

                                <tbody>
                                    {include file="addons/product_variations/views/product_variations/components/product_item.tpl"}
                                </tbody>
                                <tbody data-ca-switch-id="product_variations_group_{$product.product_id}">
                            {else}
                                {include file="addons/product_variations/views/product_variations/components/product_item.tpl"}
                            {/if}
                        {/foreach}
                        </tbody>
                    </table>
                </div>

                <div class="hidden">
                    {foreach $selected_features as $feature}
                        <select class="js-product-variation-feature" data-ca-feature-id="{$feature.feature_id}">
                            {foreach $feature.variants as $variant}
                                <option value="{$variant.variant_id}">{$variant.variant}</option>
                            {/foreach}
                        </select>
                    {/foreach}
                </div>
            {/capture}

            {include file="common/context_menu_wrapper.tpl"
                id=$context_menu_id
                form="manage_variation_products_form"
                object="product_variations"
                items=$smarty.capture.manage_variation_products_table
            }
        {else}
            <p class="no-items">{__("product_variations.add_variations_description")}</p>
        {/if}
    </form>
<!--content_variations--></div>
