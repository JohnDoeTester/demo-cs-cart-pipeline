{if $parent_id}
<div class="hidden" id="cat_{$parent_id}">
{/if}
{foreach $categories_tree as $category}
    {$comb_id="cat_`$category.category_id`"}

    {if $category.storefront_categories !== "YesNo::YES"|enum}
        <div class="longtap-selection" data-ca-bulkedit-component="tableWrapper">
    {/if}
    <table class="table table-tree table-middle table--relative table--overflow-hidden">
        {if $header && !$parent_id}
            {$header=""}
            <thead data-ca-bulkedit-default-object="true" data-ca-bulkedit-component="defaultObject">
                <tr>
                    {hook name="categories:categories_tree_header"}
                    <th class="mobile-hide table__check-items-column">
                        {include file="common/check_items.tpl"
                            check_statuses=''|fn_get_default_status_filters:true
                            is_check_disabled=$hide_inputs
                            meta="table__check-items"
                        }
                        <input type="checkbox"
                            class="bulkedit-toggler hide"
                            data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]" 
                            data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                        />
                    </th>
                    <th width="13%" class="mobile-hide table__first-column">
                        {__("position_short")}
                    </th>
                    <th width="60%">
                        {if $show_all && !$smarty.request.b_id}
                            <div class="pull-left">
                                <span alt="{__("expand_collapse_list")}" title="{__("expand_collapse_list")}" id="on_cat" class="cm-combinations{if $expand_all} hidden{/if}"><span class="icon-caret-right"> </span></span>
                                <span alt="{__("expand_collapse_list")}" title="{__("expand_collapse_list")}" id="off_cat" class="cm-combinations{if !$expand_all} hidden{/if}"><span class="icon-caret-down"> </span></span>
                            </div>
                        {/if}
                        &nbsp;{__("name")}
                    </th>
                    <th width="12%" class="center">
                        {__("products")}
                    </th>
                    <th width="5%" class="center mobile-hide">&nbsp;</th>
                    <th width="10%" class="right mobile-hide">
                        {__("status")}
                    </th>
                    {/hook}
                </tr>
            </thead>
        {/if}
        {hook name="categories:categories_tree_tr"}
        <tr class="{if $category.level > 0}multiple-table-row {/if} {if $category.status}cm-row-status-{$category.status|lower} {/if}cm-longtap-target longtap-selection"
            {if $category.storefront_categories !== "YesNo::YES"|enum}
                data-ca-longtap-action="setCheckBox"
                data-ca-longtap-target="input.cm-item"
                data-ca-id="{$category.category_id}"
            {/if}
        >
            {math equation="x*14" x=$category.level|default:"0" assign="shift"}
            {if $category.storefront_categories}
                {hook name="categories:company_categories_tree_row"}
                {$comb_id="comp_`$category.storefront_id`"}
                <td class="mobile-hide table__check-items-cell">
                    &nbsp;</td>
                <td width="13%" class="mobile-hide table__first-column">
                    &nbsp;</td>
                <td width="60%">
                    {strip}
                    <span class="categories-company" style="padding-{$direction}: {$shift}px;">
                    {if $show_all}
                        <span alt="{__("expand_sublist_of_items")}" title="{__("expand_sublist_of_items")}" id="on_{$comb_id}" class="cm-combination {if $expand_all}hidden{/if}"><span class="icon-caret-right"></span> </span>
                        {else}
                        <span alt="{__("expand_sublist_of_items")}" title="{__("expand_sublist_of_items")}" id="on_{$comb_id}" class="cm-combination" onclick="if (!Tygh.$('#{$comb_id}').children().get(0)) Tygh.$.ceAjax('request', '{"categories.manage?category_id=`$category.category_id`"|fn_url nofilter}', {$ldelim}result_ids: '{$comb_id}'{$rdelim})"> <span class="icon-caret-right"></span></span>
                    {/if}
                    <span alt="{__("collapse_sublist_of_items")}" title="{__("collapse_sublist_of_items")}" id="off_{$comb_id}" class="cm-combination{if !$expand_all || !$show_all} hidden{/if}"><span class="icon-caret-down"></span></span>
                    <span class="row-status">{$category.category}</span>
                    </span>
                    {/strip}
                </td>
                <td width="12%" class="center">
                    &nbsp;</td>
                <td width="10%" class="center mobile-hide">
                    &nbsp;</td>
                <td width="10%" class="right mobile-hide">
                    &nbsp;</td>
                {/hook}
            {else}
                {hook name="categories:categories_tree_row"}
                <td class="mobile-hide table__check-items-cell">
                    <input type="checkbox" name="category_ids[]" value="{$category.category_id}" class="hide checkbox cm-item{if $category.status} cm-item-status-{$category.status|lower}{/if}" /></td>
                <td width="13%" class="mobile-hide table__first-column">
                    <input type="text" name="categories_data[{$category.category_id}][position]" value="{$category.position}" size="3" class="input-micro input-hidden" {if $hide_inputs}disabled="disabled"{/if} /></td>
                <td width="60%">
                    {strip}
                    <span style="padding-{$direction}: {$shift}px;">
                        {if $category.has_children || $category.subcategories}
                                {if $show_all}
                                    <a href="#" alt="{__("expand_sublist_of_items")}" title="{__("expand_sublist_of_items")}" id="on_cat_{$category.category_id}" class="cm-combination {if $expand_all}hidden{/if}" ><span class="icon-caret-right"> </span></a>
                                {else}
                                    <a href="#" alt="{__("expand_sublist_of_items")}" title="{__("expand_sublist_of_items")}" id="on_cat_{$category.category_id}" class="cm-combination"><span class="icon-caret-right" onclick="if (!Tygh.$('#cat_{$category.category_id}').children().get(0)) Tygh.$.ceAjax('request', '{"categories.manage?category_id=`$category.category_id`"|fn_url nofilter}', {$ldelim}result_ids: 'cat_{$category.category_id}'{$rdelim})"> </span></a>
                                {/if}
                                <a href="#" alt="{__("collapse_sublist_of_items")}" title="{__("collapse_sublist_of_items")}" id="off_cat_{$category.category_id}" class="cm-combination{if !$expand_all || !$show_all} hidden{/if}" ><span class="icon-caret-down"> </span></a>
                        {/if}
                        <a class="row-status {if $category.status == "N"} manage-root-item-disabled{/if}{if !$category.subcategories} normal{/if}" href="{"categories.update?category_id=`$category.category_id`"|fn_url}"{if !$category.has_children && !$category.subcategories} style="padding-{$direction}: 14px;"{/if} >
                            {$category.category}
                        </a>
                        {if $category.status == "N"}&nbsp;<span class="small-note">-&nbsp;[{__("disabled")}]</span>{/if}
                    </span>
                    {/strip}
                </td>
                <td width="12%" class="center">
                    <a href="{"products.manage?cid=`$category.category_id`"|fn_url}" class="badge">{$category.product_count}</a>
                </td>
                <td width="10%" class="center mobile-hide">
                    <div class="hidden-tools">
                        {capture name="tools_items"}
                            <li>{btn type="list" text=__("add_product") href="products.add?category_id=`$category.category_id`"}</li>
                            {if !$hide_inputs}
                            <li class="divider"></li>
                            {/if}
                            <li>{btn type="list" text=__("edit") href="categories.update?category_id=`$category.category_id`"}</li>
                            <li>{btn type="list" class="cm-confirm" data=["data-ca-confirm-text" => "{__("category_deletion_side_effects")}"] text=__("delete") href="categories.delete?category_id=`$category.category_id`" method="POST"}</li>
                        {/capture}
                        {dropdown content=$smarty.capture.tools_items}
                    </div>
                </td>
                <td width="10%" class="nowrap right mobile-hide">
                    {include file="common/select_popup.tpl" popup_additional_class="dropleft" id=$category.category_id status=$category.status hidden=true object_id_name="category_id" table="categories" non_editable=$hide_inputs}
                </td>
                {/hook}
            {/if}
        </tr>
        {/hook}
    </table>
    
    {if $category.has_children || $category.subcategories}
        <div class="{if !$expand_all} hidden{/if}" id="{$comb_id}">
            {if $category.subcategories}
                {include file="views/categories/components/categories_tree.tpl"
                    categories_tree=$category.subcategories
                    parent_id=false
                    direction=$direction
                }
            {/if}
        <!--{$comb_id}--></div>
    {/if}
    
    {if $category.storefront_categories !== "YesNo::YES"|enum}
        </div>
    {/if}
{/foreach}

{if $parent_id}<!--cat_{$parent_id}--></div>{/if}
