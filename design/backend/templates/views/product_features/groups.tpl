{script src="js/tygh/tabs.js"}

{capture name="mainbox"}

    {include file="common/pagination.tpl"}

    {$r_url = $config.current_url|escape:url}
    {$show_in_popup = false}
    {$has_available_features = empty($runtime.company_id) || in_array($runtime.company_id, array_column($features, 'company_id'))}

    <form action="{""|fn_url}" method="post" name="manage_product_features_form" id="manage_product_features_form">
    <input type="hidden" name="return_url" value="{$config.current_url}">
    <input type="hidden" name="redirect_url" value="{$config.current_url}">

    <div class="items-container{if ""|fn_check_form_permissions} cm-hide-inputs{/if}" id="update_features_list">
        {if $features}
            {capture name="product_features_groups_table"}
            <div class="table-responsive-wrapper longtap-selection">
                <table width="100%" class="table table-middle table--relative table-responsive">
                    <thead
                            data-ca-bulkedit-default-object="true"
                            data-ca-bulkedit-component="defaultObject"
                    >
                    <tr>
                        <th class="left" width="6%">
                            {include file="common/check_items.tpl" 
                                check_statuses=""|fn_get_default_status_filters:true
                                is_check_disabled=!$has_available_features
                            }

                            <input type="checkbox"
                                   class="bulkedit-toggler hide"
                                   data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                                   data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                            />
                        </th>
                        <th width="20%">{__("name")}{include file="common/tooltip.tpl" tooltip=__("internal_feature_group_name_tooltip")} / {__("storefront_name")}</th>
                        <th width="30%">{__("features")}</th>
                        <th width="30%">{__("categories")}</th>
                        <th width="5%">&nbsp;</th>
                        <th width="10%" class="right">{__("status")}</th>
                    </tr>
                    </thead>
                    <tbody>
                    {foreach $features as $p_feature}
                        {$non_editable = !$is_allowed_to_change || !$p_feature|fn_allow_save_object:"product_features"}
                        {$feature_category_ids = ($p_feature.categories_path) ? (","|explode:$p_feature.categories_path) : ([])}
                        {$href_edit="product_features.update?feature_id=`$p_feature.feature_id`&return_url=`$r_url`"}
                        {$href_delete="product_features.delete?feature_id=`$p_feature.feature_id`&return_url=$r_url"}
                        {$included_features_href = "product_features.manage?parent_id=`$p_feature.feature_id`"|fn_url}

                        {$top_features_names = []}
                        {foreach $p_feature.top_features as $top_feature_id => $top_feature}
                            {$top_features_names[] = $top_feature.internal_name}
                        {/foreach}

                        <tr class="cm-row-item cm-row-status-{$p_feature.status|lower} cm-longtap-target{if $non_editable} longtap-selection-disable{/if}"
                            data-ct-product_features="{$p_feature.feature_id}"
                            data-ca-longtap-action="setCheckBox"
                            data-ca-longtap-target="input.cm-item"
                            data-ca-id="{$p_feature.feature_id}"
                            data-ca-category-ids="{$feature_category_ids|to_json}"
                            data-ca-feature-group="false"
                            {if $non_editable && $is_allowed_to_change}
                                {if fn_allowed_for("MULTIVENDOR")}
                                    data-ca-bulkedit-disabled-notice="{__("product_features_groups_of_marketplace_are_not_selectable")}"
                                {else}
                                    data-ca-bulkedit-disabled-notice="{__("product_feature_groups_are_not_selectable_for_context_menu")}"
                                {/if}
                            {/if}
                        >
                            <td width="6%" class="left" data-th="&nbsp;">
                                <input type="checkbox" name="feature_ids[]" value="{$p_feature.feature_id}" class="cm-item cm-item-status-{$p_feature.status|lower} hide" />
                            </td>
                            <td width="20%" data-th="{__("name")}">
                                <div class="object-group-link-wrap">
                                    <a class="row-status cm-external-click {if $non_editable} no-underline{/if}" data-ca-external-click-id="opener_group{$p_feature.feature_id}">{$p_feature.internal_name}</a>
                                    <span class="muted"><small> #{$p_feature.feature_id}</small></span>
                                    <div><small>{$p_feature.description}</small></div>
                                    {include file="views/companies/components/company_name.tpl" object=$p_feature}
                                </div>
                            </td>
                            <td width="30%" data-th="{__("features")}">
                                <div class="row-status object-group-details">
                                    {if $top_features_names|count > 0}
                                        <span>
                                            {", "|implode:$top_features_names}{if $p_feature.top_features|count < $p_feature.features_count},...{/if}
                                        </span>
                                        <a href="{$included_features_href}">({$p_feature.features_count nofilter})</a>
                                    {/if}
                                </div>
                            </td>
                            <td width="30%" data-th="{__("categories")}">
                                <div class="row-status object-group-details">
                                    {$p_feature.feature_description nofilter}
                                </div>
                            </td>
                            <td width="5%" class="nowrap" data-th="&nbsp;">
                                <div class="hidden-tools">
                                    {capture name="tools_list"}
                                        {if !$non_editable}
                                            <li>{include file="common/popupbox.tpl" id="group`$p_feature.feature_id`" text=$p_feature.description act="edit" href=$href_edit no_icon_link=true}</li>
                                            <li>{btn type="text" text=__("delete") href=$href_delete class="cm-confirm cm-ajax cm-ajax-force cm-ajax-full-render cm-delete-row" data=["data-ca-target-id" => "pagination_contents"] method="POST"}</li>
                                        {else}
                                            <li>{include file="common/popupbox.tpl" id="group`$p_feature.feature_id`" text=$p_feature.description act="edit" link_text=__("view") href=$href_edit no_icon_link=true}</li>
                                        {/if}
                                    {/capture}
                                    {dropdown content=$smarty.capture.tools_list}
                                </div>
                            </td>
                            <td width="10%" class="right nowrap" data-th="{__("status")}">
                                {include file="common/select_popup.tpl" popup_additional_class="dropleft" id=$p_feature.feature_id status=$p_feature.status hidden=true object_id_name="feature_id" table="product_features" update_controller="product_features"}
                            </td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>
            {/capture}

            {include file="common/context_menu_wrapper.tpl"
                form="manage_product_features_form"
                object="product_features_groups"
                items=$smarty.capture.product_features_groups_table
            }
        {else}
            <p class="no-items">{__("no_data")}</p>
        {/if}
    <!--update_features_list--></div>
    </form>

    {include file="common/pagination.tpl"}
    {capture name="adv_buttons"}
        {capture name="add_new_picker"}
            {include file="views/product_features/update.tpl" feature=[] in_popup=true is_group=true return_url=$config.current_url}
        {/capture}
        {include file="common/popupbox.tpl" id="add_new_feature" text=__("new_group") title=__("new_group") content=$smarty.capture.add_new_picker act="general" icon="icon-plus"}
    {/capture}

    {capture name="sidebar"}
        {include file="views/product_features/components/product_feature_groups_search_form.tpl" dispatch="product_features.groups"}
    {/capture}

{/capture}
{include file="common/mainbox.tpl" title=__("feature_groups") content=$smarty.capture.mainbox select_languages=true adv_buttons=$smarty.capture.adv_buttons sidebar=$smarty.capture.sidebar}
