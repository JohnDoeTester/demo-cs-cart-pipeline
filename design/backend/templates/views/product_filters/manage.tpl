{script src="js/tygh/tabs.js"}

<script>
    var filter_fields = {ldelim}{rdelim};
    {foreach from=$filter_fields item=filter_field key=key}
    filter_fields['{$key}'] = '{$filter_field.slider}';
    {/foreach}

{literal}
function fn_check_product_filter_type(value, tab_id, id)
{
    var $ = Tygh.$;
    if (!value) { return; }
    $('#' + tab_id).toggleBy(!(value.indexOf('R') == 0) && !(value.indexOf('D') == 0));
    $('[id^=inputs_ranges' + id + ']').toggleBy((value.indexOf('D') == 0));
    $('[id^=dates_ranges' + id + ']').toggleBy(!(value.indexOf('D') == 0));
    $('#round_to_' + id + '_container').toggleBy(!filter_fields[value.replace(/\w+-/, '')]);
    $('#display_count_' + id + '_container').toggleBy(!(value.indexOf('R') == 0) && !(value.indexOf('F') == 0) && !(value.indexOf('S') > 0));
}
{/literal}
</script>

{capture name="mainbox"}

<form action="{""|fn_url}" method="post" class="cm-disable-check-changes" name="manage_product_filters_form" id="manage_product_filters_form">
{include file="common/pagination.tpl" object_type="filters"}
<input type="hidden" name="redirect_url" value="{$config.current_url}">

{$r_url=$config.current_url|escape:url}
{$has_available_filters = empty($runtime.company_id) || in_array($runtime.company_id, array_column($filters, 'company_id'))}

{capture name="product_filters_table"}
<div class="items-container{if ""|fn_check_form_permissions} cm-hide-inputs{else} cm-sortable{/if}" data-ca-sortable-table="product_filters" data-ca-sortable-id-name="filter_id" id="manage_filters_list">
    {if $filters}
        <div class="table-responsive-wrapper longtap-selection">
            <table width="100%" class="table table-middle table--relative table-objects table-striped table-responsive table-responsive-w-titles">
                <thead
                        data-ca-bulkedit-default-object="true"
                        data-ca-bulkedit-component="defaultObject"
                >
                    <tr>
                        <th>
                            {include file="common/check_items.tpl" is_check_disabled=!$has_available_filters}

                            <input type="checkbox"
                                class="bulkedit-toggler hide"
                                data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                                data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                            />
                        </th>
                        <th>&nbsp;</th>
                        <th>&nbsp;</th>
                        <th>&nbsp;</th>
                        <th>&nbsp;</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
            <tbody>

            {foreach $filters as $filter}

                {if $filter|fn_allow_save_object:"product_filters"}
                    {include file="common/object_group.tpl"
                        id=$filter.filter_id
                        show_id=true
                        details=$filter.filter_description
                        text=$filter.filter
                        status=$filter.status
                        href="product_filters.update?filter_id=`$filter.filter_id`&return_url=$r_url&in_popup"
                        object_id_name="filter_id"
                        href_delete="product_filters.delete?filter_id=`$filter.filter_id`"
                        delete_target_id="manage_filters_list,actions_panel,pagination_contents"
                        table="product_filters"
                        no_table=true
                        draggable=true
                        additional_class="cm-no-hide-input cm-sortable-row cm-sortable-id-`$filter.filter_id` cm-longtap-target"
                        header_text=$filter.filter
                        link_text=__("edit")
                        company_object=$filter
                        is_responsive_table=true
                        is_bulkedit_menu=true
                        checkbox_col_width="1%"
                        checkbox_name="filter_ids[]"
                        hidden_checkbox=true
                        bulkedit_menu_category_ids="[`$filter.categories_path`]"
                        show_checkboxes=true
                        checkbox_col_width="6%"
                    }
                {else}
                    {include file="common/object_group.tpl"
                        id=$filter.filter_id
                        show_id=true
                        details=$filter.filter_description
                        text=$filter.filter
                        status=$filter.status
                        href="product_filters.update?filter_id=`$filter.filter_id`&return_url=$r_url&in_popup"
                        object_id_name="filter_id"
                        table="product_filters"
                        no_table=true
                        additional_class="cm-sortable-row cm-sortable-id-`$filter.filter_id`"
                        header_text="{__("viewing_filter")}:&nbsp;`$filter.filter`"
                        link_text=__("view")
                        non_editable=true
                        is_view_link=true
                        hidden_checkbox=true
                        company_object=$filter
                        bulkedit_disabled_notice="{__("product_filters_are_not_selectable_for_context_menu")}"
                        is_bulkedit_menu=true
                        checkbox_col_width="6%"
                        checkbox_name="filter_ids[]"
                        show_checkboxes=true
                    }
                {/if}

            {/foreach}
            </tbody>
            </table>
        </div>
    {else}
        <p class="no-items">{__("no_data")}</p>
    {/if}
<!--manage_filters_list--></div>
{/capture}

{include file="common/context_menu_wrapper.tpl"
    form="manage_product_filters_form"
    object="product_filters"
    items=$smarty.capture.product_filters_table
}

{include file="common/pagination.tpl" object_type="filters"}

</form>

{capture name="adv_buttons"}
    {capture name="add_new_picker"}
        {include file="views/product_filters/update.tpl" filter=[] in_popup=true}
    {/capture}
    {if !"MULTIVENDOR"|fn_allowed_for || (!$runtime.company_id && "MULTIVENDOR"|fn_allowed_for)}
    {if !$filter_fields && !$filter_features}
        {$add_filter_button_meta = "cm-disabled disabled" }
        {$add_filter_button_tooltip = __("filters_in_use") }
    {else}
        {$add_filter_button_tooltip = __("add_filter") }
    {/if}
    {include file="common/popupbox.tpl" id="add_product_filter" text=__("new_filter") content=$smarty.capture.add_new_picker title=$add_filter_button_tooltip act="general" icon="icon-plus"  link_class=$add_filter_button_meta }
    {/if}

{/capture}

{/capture}

{capture name="sidebar"}
{include file="common/saved_search.tpl" dispatch="product_filters.manage" view_type="product_filters"}
{include file="views/product_filters/components/product_filters_search_form.tpl" dispatch="product_filters.manage"}
{/capture}

{include file="common/mainbox.tpl" title=__("filters") content=$smarty.capture.mainbox adv_buttons=$smarty.capture.adv_buttons sidebar=$smarty.capture.sidebar select_languages=true}
