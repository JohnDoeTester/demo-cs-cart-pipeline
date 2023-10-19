<div id="generate_product_group_form">
    {if $selected_features}
        {include
            file="views/products/components/products_search_form.tpl"
            dispatch="product_variations.find_variations"
            extra="<input type=\"hidden\" name=\"result_ids\" value=\"product_group_form_list,tools_tab_link_existing_{$product_data.product_id}\">"
            put_request_vars=true
            form_meta="cm-ajax"
            in_popup=true
            show_product_type_filter=false
            show_product_parent_filter=false
        }
    {/if}

    <form action="{"product_variations.link"|fn_url}" class="form-horizontal form-edit" name="add_product_to_group_form" method="post">
        <input type="hidden" name="product_id" value="{$product_data.product_id}" />

        <div class="items-container" id="product_group_form_list">
            {if $selected_features}
                {if $products}
                    {include file="common/pagination.tpl" div_id="product_group_form_list" disable_history=true}

                    <table width="100%" class="table table-middle table--relative">
                        <thead>
                        <tr>
                            <th class="center" width="1%">
                                {include file="common/check_items.tpl"}
                            </th>
                            <th width="5%"><span>{__("image")}</span></th>
                            <th width="25%"><span>{__("name")} / {__("sku")}</span></th>
                            {foreach $selected_features as $feature}
                                <th width="10%"><span>{$feature.description}</span></th>
                            {/foreach}
                        </tr>
                        </thead>
                        <tbody>
                        {foreach $products as $product}
                            <tr>
                                <td class="center" width="1%">
                                    <input type="checkbox" name="product_ids[]" value="{$product.product_id}" class="cm-item mrg-check" id="checkbox_id_{$product.product_id}" />
                                </td>
                                <td>
                                    {include file="common/image.tpl" image=$product.main_pair.icon|default:$product.main_pair.detailed image_id=$product.main_pair.image_id image_width=$settings.Thumbnails.product_admin_mini_icon_width image_height=$settings.Thumbnails.product_admin_mini_icon_height href="products.update?product_id=`$product.product_id`"|fn_url}
                                </td>
                                <td>
                                    <a href="{"products.update?product_id=`$product.product_id`"|fn_url}">{$product.product nofilter}</a>
                                    <div class="product-list__labels">
                                        <div class="product-code">
                                            <span class="product-code__label">{$product.product_code}</span>
                                        </div>
                                    </div>
                                    {include file="views/companies/components/company_name.tpl" object=$product}
                                </td>

                                {foreach $selected_features as $feature}
                                    <td width="10%"><span>{$product.variation_features[$feature.feature_id].variant}</span></td>
                                {/foreach}
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>

                    {include file="common/pagination.tpl" div_id="product_group_form_list" disable_history=true}
                {else}
                    <p class="no-items">{__("no_data")}</p>
                {/if}
            {else}
                <div class="no-items row-fluid">
                    <div class="span8 offset2 left">{__("product_variations.no_available_features", ["[manage_features_href]" => "product_features.manage"|fn_url]) nofilter}</div>
                </div>
            {/if}
        <!--product_group_form_list--></div>
    </form>
<!--generate_product_group_form--></div>