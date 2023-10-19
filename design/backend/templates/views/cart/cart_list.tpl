{capture name="mainbox"}

<form action="{""|fn_url}" method="post" target="" name="carts_list_form">

{include file="common/pagination.tpl" save_current_url=true}

{$c_url = $config.current_url|fn_query_remove:"sort_by":"sort_order"}
{$has_permission_update = fn_check_permissions("cart", "delete", "admin", "POST")}

{if $carts_list}
    {capture name="carts_table"}
        <div class="table-responsive-wrapper longtap-selection">
            <table class="table table-sort table-middle table--relative table-responsive table--overflow-hidden table--cart">
            <thead
                data-ca-bulkedit-default-object="true"
                data-ca-bulkedit-component="defaultObject"
            >
                <tr>
                    <th class="mobile-hide table__check-items-column">
                        {include file="common/check_items.tpl"
                            is_check_all_shown=true
                            is_check_disabled=!$has_permission_update
                            meta="table__check-items"
                        }

                        <input type="checkbox"
                            class="bulkedit-toggler hide"
                            data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                            data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                        />
                    </th>
                    <th width="40%">
                        <div class="btn-expand-wrapper">
                            <span id="off_carts" alt="{__("expand_collapse_list")}" title="{__("expand_collapse_list")}" class="hidden hand cm-combinations-carts btn-expand btn-expand--header"/><span class="icon-caret-down"></span></span>
                            <span id="on_carts" alt="{__("expand_collapse_list")}" title="{__("expand_collapse_list")}" class="cm-combinations-carts btn-expand btn-expand--header"><span class="icon-caret-right"></span></span>
                        </div>
                        {include file="common/table_col_head.tpl" type="customer"}
                    </th>
                    <th width="14%">
                        {include file="common/table_col_head.tpl" type="date"}
                    <th width="11%">
                        {include file="common/table_col_head.tpl" text=__("cart")}
                    </th>
                    {hook name="cart:items_list_header"}
                    {/hook}
                    <th width="22%" class="mobile-hide">&nbsp;</th>
                    <th width="13%" class="right">
                        {include file="common/table_col_head.tpl" text=__("total")}
                    </th>
                </tr>
            </thead>
            {foreach $carts_list as $customer}
            <tr
                {if $has_permission_update}
                    data-ca-longtap-action="setCheckBox"
                    data-ca-longtap-target="input.cm-item"
                    data-ca-id="{(!"ULTIMATE"|fn_allowed_for && $customer.storefront_id) ? ($customer.storefront_id) : ($customer.user_id)}"
                {/if}
            >
                {$user_data = $customer.user_data}
                {if !$user_data.s_country_descr && $countries[$user_data.s_country]}
                    {$user_data.s_country_descr = $countries[$user_data.s_country]}
                {/if}
                {if !$user_data.s_state_descr}
                    {foreach $states[$user_data.s_country] as $state}
                        {if $state.code === $user_data.s_state}
                            {$user_data.s_state_descr = $state.state}
                            {break}
                        {/if}
                    {/foreach}
                {/if}
                <td class="left mobile-hide table__check-items-cell">
                    {if "ULTIMATE"|fn_allowed_for}
                        <input type="checkbox" name="user_ids[{$customer.company_id}][]" value="{$customer.user_id}" class="cm-item hide" />
                    {/if}
                    {if !"ULTIMATE"|fn_allowed_for}
                        <input type="checkbox" name="user_ids[]" value="{$customer.user_id}" class="cm-item hide" />
                        {if $customer.storefront_id}
                            <input type="hidden" name="storefront_id" value="{$customer.storefront_id}" class="cm-item" />
                        {/if}
                    {/if}
                </td>
                <td width="40%" data-th="{__("customer")}">
                    <div class="cart__customer">
                        {if "ULTIMATE"|fn_allowed_for}
                            <a href="#" alt="{__("expand_sublist_of_items")}" title="{__("expand_sublist_of_items")}" id="on_user_{$customer.user_id}_{$customer.company_id}" class="cm-combination-carts btn-expand" onclick="Tygh.$.ceAjax('request', '{"cart.cart_list?user_id=`$customer.user_id`&c_company_id=`$customer.company_id`"|fn_url nofilter}', {$ldelim}result_ids: 'cart_products_{$customer.user_id}_{$customer.company_id},wishlist_products_{$customer.user_id}_{$customer.company_id}', caching: true{$rdelim});"><span class="icon-caret-right"></span></a>
                            <a href="#" alt="{__("collapse_sublist_of_items")}" title="{__("collapse_sublist_of_items")}" id="off_user_{$customer.user_id}_{$customer.company_id}" class="hidden cm-combination-carts btn-expand"><span class="icon-caret-down"></span></a>
                        {/if}

                        {if !"ULTIMATE"|fn_allowed_for}
                            {if $customer.storefront_id}
                                <a href="#" alt="{__("expand_sublist_of_items")}" title="{__("expand_sublist_of_items")}" id="on_user_{$customer.user_id}" class="cm-combination-carts btn-expand" onclick="Tygh.$.ceAjax('request', '{"cart.cart_list?user_id=`$customer.user_id`&storefront_id=`$customer.storefront_id`"|fn_url nofilter}', {$ldelim}result_ids: 'cart_products_{$customer.user_id},wishlist_products_{$customer.user_id}', caching: true{$rdelim});"><span class="icon-caret-right"></span></a>
                            {else}
                                <a href="#" alt="{__("expand_sublist_of_items")}" title="{__("expand_sublist_of_items")}" id="on_user_{$customer.user_id}" class="cm-combination-carts btn-expand" onclick="Tygh.$.ceAjax('request', '{"cart.cart_list?user_id=`$customer.user_id`"|fn_url nofilter}', {$ldelim}result_ids: 'cart_products_{$customer.user_id},wishlist_products_{$customer.user_id}', caching: true{$rdelim});"><span class="icon-caret-right"></span></a>
                            {/if}
                            <a href="#" alt="{__("collapse_sublist_of_items")}" title="{__("collapse_sublist_of_items")}" id="off_user_{$customer.user_id}" class="hidden cm-combination-carts btn-expand"><span class="icon-caret-down"></span></a>
                        {/if}

                        <div class="cart__customer-data-wrapper">
                            <div class="cart__customer-data">
                                {if $user_data.email}
                                    <a href="mailto:{$user_data.email|escape:url}" class="cart__customer-email">@</a>
                                    {if $user_data.user_id}<a href="{"profiles.update?user_id=`$customer.user_id`"|fn_url}">{/if}
                                        <span class="cart__customer-name">
                                            {if $customer.firstname || $customer.lastname}
                                                {$customer.firstname} {$customer.lastname}
                                            {else}
                                                {$user_data.email}
                                            {/if}
                                        </span>
                                    {if $user_data.user_id}</a>{/if}
                                {else}
                                    {if $customer.email}<a href="mailto:{$customer.email|escape:url}" class="cart__customer-email">@</a>{/if}
                                    <span class="cart__customer-name">
                                        {if $customer.lastname || $customer.firstname}
                                                {$customer.firstname} {$customer.lastname}
                                        {else}
                                            {__("unregistered_customer_short")}
                                        {/if}
                                    </span>
                                {/if}
                                {if $user_data.s_state_descr || $user_data.s_country}
                                    <span class="muted">{$user_data.s_state_descr|truncate:25:"...":true}{if $user_data.s_state_descr}, {/if}{$user_data.s_country}</span>
                                {else}
                                {/if}
                            </div>
                            {if $customer.phone}
                                <div class="cart__customer-phone">
                                    <a href="tel:{$customer.phone}">{$customer.phone}</a>
                                </div>
                            {/if}

                            {include file="views/companies/components/company_name.tpl" object=$customer}

                        </div>
                    </div>
                </td>
                <td width="14%" data-th="{__("date")}">
                    {$customer.date|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
                </td>
                <td width="11%" data-th="{__("cart")}">
                    {__("n_products", [$customer.cart_products|default:"0"])}
                    {hook name="cart:cart_content"}
                        {if $customer.order_id}
                        <div><small><a href="{"orders.details?order_id=`$customer.order_id`"|fn_url}">{__("order")} <bdi>#{$customer.order_id}</bdi></a></small></div>
                        {/if}
                    {/hook}
                </td>
                {hook name="cart:items_list"}
                {/hook}
                <td width="22%" class="center" data-th="{__("tools")}">
                    {capture name="tools_items"}
                        {hook name="cart:list_extra_links"}
                            {if "cart.delete"|fn_check_view_permissions}
                                {$current_redirect_url = $config.current_url|escape:url}
                                {if $customer.storefront_id}
                                    {$delete_url = "cart.delete?user_id=`$customer.user_id`&redirect_url=`$current_redirect_url`&storefront_id=`$customer.storefront_id`"}
                                {else}
                                    {$delete_url = "cart.delete?user_id=`$customer.user_id`&redirect_url=`$current_redirect_url`"}
                                {/if}
                                {if "ULTIMATE"|fn_allowed_for}
                                    {$delete_url = "{$delete_url}&company_id={$customer.company_id}"}
                                {/if}
                                <li>{btn type="list" href=$delete_url class="cm-confirm" text={__("delete")} method="POST"}</li>
                            {/if}
                        {/hook}
                    {/capture}
                    <div class="hidden-tools">
                        <div class="btn-group">
                            {if $customer.storefront_id}
                                {include file="buttons/button.tpl" but_role="action" but_text=__("add_as_order") but_href="cart.convert_to_order?user_id=`$customer.user_id`&storefront_id=`$customer.storefront_id`" but_meta="cm-post"}
                            {else}
                                {include file="buttons/button.tpl" but_role="action" but_text=__("add_as_order") but_href="cart.convert_to_order?user_id=`$customer.user_id`" but_meta="cm-post"}
                            {/if}
                            {if $smarty.capture.tools_items|trim}
                                <button class="btn dropdown-toggle" data-toggle="dropdown">
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu">
                                    {$smarty.capture.tools_items nofilter}
                                </ul>
                            {/if}
                        </div>
                    </div>
                </td>
                <td width="13%" class="right" data-th="{__("total")}">
                    {include file="common/price.tpl" value=$customer.total}
                </td>
            </tr>
            {$user_js_id = "user_`$customer.user_id`"}
            {if "ULTIMATE"|fn_allowed_for}
                {$user_js_id = "`$user_js_id`_`$customer.company_id`"}
            {/if}
            <tbody id="{$user_js_id}" class="hidden row-more">
                <tr class="no-border">
                    <td colspan="100%" class="row-more-body top row-gray cart__detailed-wrapper">
                        <div class="cart__detailed">

                        {if $user_data}
                            {$user_info_js_id = "user_info_`$customer.user_id`"}
                            {if "ULTIMATE"|fn_allowed_for}
                                {$user_info_js_id = "`$user_info_js_id`_`$customer.company_id`"}
                            {/if}
                            <div class="cart__detailed-user">
                                <div id="{$user_info_js_id}">
                                    <h4>{__("user_info")}</h4>
                                    <dl>
                                        {if $user_data.email}
                                            <dt>{__("email")}</dt>
                                            <dd><a href="mailto:{$user_data.email|escape:url}">{$user_data.email}</a></dd>
                                        {/if}
                                        {if $customer.phone}
                                            <dt>{__("phone")}</dt>
                                            <dd><a href="tel:{$customer.phone}">{$customer.phone}</a></dd>
                                        {/if}
                                        <dt>{__("first_name")}</dt>
                                        <dd>{$user_data.firstname}</dd>
                                        <dt>{__("last_name")}</dt>
                                        <dd>{$user_data.lastname}</dd>
                                        {if $customer.ip_address}
                                            <dt>{__("ip_address")}</dt>
                                            <dd>{$customer.ip_address}</dd>
                                        {/if}
                                    </dl>

                                    {if $user_data.ship_to_another}
                                        <h4>{__("billing_address")}</h4>
                                    {else}
                                        <h4>{__("billing_shipping_address")}</h4>
                                    {/if}
                                    <dl>
                                        <dt>{__("first_name")}</dt>
                                        <dd>{$user_data.s_firstname}</dd>
                                        <dt>{__("last_name")}</dt>
                                        <dd>{$user_data.s_lastname}</dd>
                                        {if $user_data.s_phone}
                                            <dt>{__("phone")}</dt>
                                            <dd>{$user_data.s_phone}</dd>
                                        {/if}
                                        <dt>{__("address")}</dt>
                                        <dd>{$user_data.s_address}</dd>
                                        {if $user_data.s_address_2}
                                            <dt>{__("address_2")}</dt>
                                            <dd>{$user_data.s_address_2}</dd>
                                        {/if}
                                        <dt>{__("city")}</dt>
                                        <dd>{$user_data.s_city}</dd>
                                        <dt>{__("state")}</dt>
                                        <dd>{$user_data.s_state_descr}</dd>
                                        <dt>{__("country")}</dt>
                                        <dd>{$user_data.s_country_descr}</dd>
                                        <dt>{__("zip_postal_code")}</dt>
                                        <dd>{$user_data.s_zipcode}</dd>
                                    </dl>

                                    {if $user_data.ship_to_another}
                                    <h4>{__("shipping_address")}</h4>
                                    <dl>
                                        <dt>{__("first_name")}</dt>
                                        <dd>{$user_data.s_firstname}</dd>
                                        <dt>{__("last_name")}</dt>
                                        <dd>{$user_data.s_lastname}</dd>
                                        {if $user_data.s_phone}
                                            <dt>{__("phone")}</dt>
                                            <dd>{$user_data.s_phone}</dd>
                                        {/if}
                                        <dt>{__("address")}</dt>
                                        <dd>{$user_data.s_address}</dd>
                                        {if $user_data.s_address_2}
                                            <dt>{__("address_2")}</dt>
                                            <dd>{$user_data.s_address_2}</dd>
                                        {/if}
                                        <dt>{__("city")}</dt>
                                        <dd>{$user_data.s_city}</dd>
                                        <dt>{__("state")}</dt>
                                        <dd>{$user_data.s_state_descr}</dd>
                                        <dt>{__("country")}</dt>
                                        <dd>{$user_data.s_country_descr}</dd>
                                        <dt>{__("zip_postal_code")}</dt>
                                        <dd>{$user_data.s_zipcode}</dd>
                                    </dl>
                                    {/if}
                                <!--{$user_info_js_id}--></div>
                                </div>
                        {else}
                            {$user_info_js_id = "user_info_`$customer.user_id`"}
                            {if "ULTIMATE"|fn_allowed_for}
                                {$user_info_js_id = "`$user_info_js_id`_`$customer.company_id`"}
                            {/if}
                            <div class="cart__detailed-user">
                                <div id="{$user_info_js_id}">

                                    <h4>{__("user_info")}</h4>
                                    <dl>
                                        {if !$customer.lastname && !$customer.firstname}
                                            <dt>{__("customer")}</dt>
                                            <dd>{__("unregistered_customer_short")}</dd>
                                        {/if}
                                        {if $customer.email}
                                            <dt>{__("email")}</dt>
                                            <dd><a href="mailto:{$customer.email|escape:url}">{$customer.email}</a></dd>
                                        {/if}
                                        {if $customer.phone}
                                            <dt>{__("phone")}</dt>
                                            <dd><a href="tel:{$customer.phone}">{$customer.phone}</a></dd>
                                        {/if}
                                        {if $customer.firstname}
                                            <dt>{__("first_name")}</dt>
                                            <dd>{$customer.firstname}</dd>
                                        {/if}
                                        {if $customer.lastname}
                                            <dt>{__("last_name")}</dt>
                                            <dd>{$customer.lastname}</dd>
                                        {/if}
                                        {if $customer.ip_address}
                                            <dt>{__("ip_address")}</dt>
                                            <dd>{$customer.ip_address}</dd>
                                        {/if}
                                    </dl>
                                <!--{$user_info_js_id}--></div>
                            </div>
                        {/if}
                        {$cart_products_js_id = "cart_products_`$customer.user_id`"}
                        {if "ULTIMATE"|fn_allowed_for}
                            {$cart_products_js_id = "`$cart_products_js_id`_`$customer.company_id`"}
                        {/if}
                        <div class="cart__detailed-products">
                        {hook name="cart:products_content"}
                            <div id="{$cart_products_js_id}">
                            {if $customer.user_id == $sl_user_id}
                                {$show_price = true}
                                {if $cart_products}
                                <div class="table-responsive-wrapper">
                                    <table class="table table-condensed table--relative table-responsive">
                                        <thead>
                                            <tr class="no-hover">
                                                <th>{__("product")}</th>
                                                <th class="center nowrap">{__("quantity")}</th>
                                                <th class="right">{__("price")}</th>
                                            </tr>
                                        </thead>
                                        {foreach $cart_products as $product}
                                        {hook name="cart:product_row"}
                                            {if !$product.extra.extra.parent}
                                                <tr>
                                                    <td data-th="{__("product")}" class="cart__detailed-products-name">
                                                    {if $product.item_type == "P"}
                                                        {if $product.product}
                                                        <a href="{"products.update?product_id=`$product.product_id`"|fn_url}">{$product.product nofilter}</a>

                                                        {hook name="cart:product_info"}
                                                        {/hook}
                                                        {else}
                                                        {__("deleted_product")}
                                                        {/if}
                                                    {/if}
                                                    {hook name="cart:products_list"}
                                                    {/hook}
                                                    </td>
                                                    <td data-th="{__("quantity")}" class="center">{$product.amount}</td>
                                                    <td data-th="{__("price")}" class="right">{include file="common/price.tpl" value=$product.price span_id="c_`$customer.user_id`_`$product.item_id`"}</td>
                                                </tr>
                                            {/if}
                                        {/hook}
                                        {/foreach}
                                        <tr>
                                            <td data-th="{__("total")}" class="right"><span class="mobile-hide">{__("total")}:</span></td>
                                            <td data-th="{__("quantity")}" class="center"><span>{$customer.cart_products}</span></td>
                                            <td data-th="{__("subtotal")}" class="right"><span>{include file="common/price.tpl" value=$customer.total span_id="u_`$customer.user_id`"}</span></td>
                                        </tr>
                                    </table>
                                </div>
                                {/if}
                            {/if}
                            <!--{$cart_products_js_id}--></div>
                        {/hook}
                        </div>
                    </div>
                </td>
                {hook name="cart:items_list_row"}
                {/hook}
            </tr>
            </tbody>
            {/foreach}
            </table>
        </div>
    {/capture}

    {include file="common/context_menu_wrapper.tpl"
        form="carts_list_form"
        object="cart"
        items=$smarty.capture.carts_table
        has_permission=$has_permission_update
        is_check_all_shown=true
    }
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl"}
</form>
{/capture}

{capture name="sidebar"}
    {hook name="cart:sidebar"}
    {include file="common/saved_search.tpl" dispatch="cart.cart_list" view_type="carts"}
    {include file="views/cart/components/carts_search_form.tpl" dispatch="cart.cart_list"}
    {/hook}
{/capture}

{include file="common/mainbox.tpl"
    title=__("users_carts")
    content=$smarty.capture.mainbox
    sidebar=$smarty.capture.sidebar
    select_storefront=true
    storefront_switcher_param_name="storefront_id"
    selected_storefront_id=$selected_storefront_id
}
