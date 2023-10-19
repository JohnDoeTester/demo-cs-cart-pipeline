{script src="js/tygh/tabs.js"}
{capture name="mainbox"}

    {include_ext file="common/icon.tpl" class="icon-`$search.sort_order_rev`" assign=c_icon}
    {include_ext file="common/icon.tpl" class="icon-dummy" assign=c_dummy}
    {$c_url=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
    {$rev=$smarty.request.content_id|default:"pagination_contents"}

    <form action="{""|fn_url}" method="post" name="manage_product_bundles_form" class="form-horizontal form-edit cm-ajax" id="manage_product_bundles_form">
        <input type="hidden" name="redirect_url" value="{$config.current_url}" />
        <div id="update_bundles_list">
        {include file="common/pagination.tpl"}
        {if $bundles}
            {$context_menu_id = "context_menu_{uniqid()}"}
            {capture name="product_bundles_table"}
                <div class="items-container">
                    <div class="table-responsive-wrapper longtap-selection">
                        <table class="table table-middle table--relative table-objects table-responsive table-responsive-w-titles">
                            <thead
                                    data-ca-bulkedit-default-object="true"
                                    data-ca-bulkedit-component="defaultObject"
                            >
                            <tr>
                                <th class="left" width="6%">
                                    {include file="common/check_items.tpl"
                                        elms_container="#`$context_menu_id`"
                                    }

                                    <input type="checkbox"
                                           class="bulkedit-toggler hide"
                                           data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                                           data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                                    />
                                </th>
                                <th width="1%"></th>
                                <th width="56%">
                                    <a class="cm-ajax{if $search.sort_by === "name"} sort-link-{$search.sort_order_rev}{/if}" href="{"`$c_url`&sort_by=name&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("product_bundles.product_bundle_name")}{include file="common/tooltip.tpl" tooltip=__("product_bundles.internal_feature_name_tooltip")}{if $search.sort_by === "name"}{$c_icon nofilter}{/if}</a> /
                                    <a class="cm-ajax{if $search.sort_by === "storefront_name"} sort-link-{$search.sort_order_rev}{/if}" href="{"`$c_url`&sort_by=storefront_name&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("storefront_name")}{if $search.sort_by === "storefront_name"}{$c_icon nofilter}{/if}</a>
                                </th>
                                <th width="12%">{__("products")}</th>
                                <th width="10%"></th>
                                <th width="12%" class="right"><a class="cm-ajax{if $search.sort_by === "status"} sort-link-{$search.sort_order_rev}{/if}" href="{"`$c_url`&sort_by=status&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("status")}{if $search.sort_by === "status"}{$c_icon nofilter}{/if}</a></th>
                            </tr>
                            </thead>
                            {foreach $bundles as $bundle}
                                {capture name="extra_data"}
                                    {if $bundle.products}
                                        <a href="{"products.manage&pid=`$bundle.product_ids`"|fn_url}">
                                            {__("n_products", [$bundle.products|count])}
                                        </a>
                                    {else}
                                        {__("n_products", [$bundle.products|count])}
                                    {/if}
                                {/capture}
                                {include file="common/object_group.tpl"
                                    id=$bundle.bundle_id
                                    id_prefix="_product_bundle_"
                                    text=$bundle.name
                                    status=$bundle.status
                                    hidden=false
                                    href="product_bundles.update?bundle_id=`$bundle.bundle_id`&return_url=`$config.current_url|escape:url`"
                                    href_col_width="56%"
                                    details=$smarty.capture.extra_data
                                    details_col_width="12%"
                                    object_id_name="bundle_id"
                                    table="product_bundles"
                                    href_delete="product_bundles.delete?bundle_id=`$bundle.bundle_id`&return_url=`$config.current_url|escape:url`"
                                    delete_target_id="update_bundles_list"
                                    header_text=$bundle.name
                                    skip_delete=false
                                    no_table=true
                                    hide_for_vendor=false
                                    is_bulkedit_menu=true
                                    checkbox_col_width="1%"
                                    checkbox_name="bundle_ids[]"
                                    show_checkboxes=!$hide_controls
                                    hidden_checkbox=true
                                    company_object=["company_id" => $bundle.company_id]
                                    storefront_name=$bundle.storefront_name
                                    checkbox_col_width="6%"
                                }
                            {/foreach}
                        </table>
                    </div>
                    </div>
            {/capture}

            {include file="common/context_menu_wrapper.tpl"
                id=$context_menu_id
                form="manage_product_bundles_form"
                object="product_bundles"
                items=$smarty.capture.product_bundles_table
            }

        {else}
            <p class="no-items">{__("no_data")}</p>
        {/if}
            <div class="clearfix">
                {include file="common/pagination.tpl"}
            </div>
        <!--update_bundles_list--></div>
    </form>

{/capture}

{capture name="adv_buttons"}
    {capture name="add_new_picker"}
        {include file="addons/product_bundles/views/product_bundles/update.tpl"
            item=$item
            hide_for_vendor=false
        }
    {/capture}
    {include file="common/popupbox.tpl"
        id="add_new_bundles"
        text=__("product_bundles.new_bundle")
        content=$smarty.capture.add_new_picker
        title=__("product_bundles.add_new_bundle")
        act="general"
        icon="icon-plus"
    }
{/capture}

{capture name="sidebar"}
    {include file="addons/product_bundles/views/product_bundles/components/sidebar.tpl"}
{/capture}

{include file="common/mainbox.tpl"
    title=__("product_bundles.product_bundles")
    content=$smarty.capture.mainbox
    select_languages=true
    buttons=$smarty.capture.buttons
    sidebar=$smarty.capture.sidebar
    adv_buttons=$smarty.capture.adv_buttons
    select_storefront="ULTIMATE"|fn_allowed_for
    storefront_switcher_param_name="storefront_id"
    selected_storefront_id=$selected_storefront_id
}