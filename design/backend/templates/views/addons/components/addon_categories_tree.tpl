{if $language_direction == "rtl"}
    {$direction = "right"}
{else}
    {$direction = "left"}
{/if}

<ul id="category_tree">

    {if !$category}
        <li {if !$smarty.request.category_id}class="active"{/if}>
            <div class="link">
                    <a class="row-status normal" href="{"addons.manage"|fn_url}"
                        {if $smarty.request.category_id}
                            style="padding-{$direction}: 14px;"
                        {/if}
                    >
                        {__("all")}
                    </a>
            </div>
        </li>
    {/if}

    {foreach $categories_tree as $category}
        {$shift = 14 * $category.level|default:0}
        {capture name="category_subtitle"}

            {$expanded = $category.category_id|in_array:$active_category_ids}

            {$comb_id = "cat_`$category.category_id`"}
            {if "MULTIVENDOR"|fn_allowed_for && $category.disabled}
                {$category.category}
            {else}
                <a class="row-status {if $category.status === "ObjectStatuses::NEW_OBJECT"|enum} manage-root-item-disabled{/if}{if !$category.subcategories} normal{/if}" href="{"addons.manage?category_id=`$category.category_id`"|fn_url}"{if !$category.subcategories} style="padding-{$direction}: 14px;"{/if} >{$category.category}</a>
            {/if}
        {/capture}
        <li {if $category.category_id === $smarty.request.category_id}class="active"{/if} style="padding-{$direction}: {$shift}px;">
            {strip}
                <div class="link">
                    {if $category.subcategories}
                        <span alt="{__("expand_sublist_of_items")}" title="{__("expand_sublist_of_items")}" id="on_{$comb_id}" class="cm-combination{if $expanded} hidden{/if}" ><span class="icon-caret-right"> </span></span>
                        <span alt="{__("collapse_sublist_of_items")}" title="{__("collapse_sublist_of_items")}" id="off_{$comb_id}" class="cm-combination{if !$expanded} hidden{/if}" ><span class="icon-caret-down"> </span></span>
                    {/if}
                    {$smarty.capture.category_subtitle nofilter}
                </div>
            {/strip}
        </li>
        {if $category.subcategories}
            <li class="{if !$expanded} hidden{/if}" id="{$comb_id}">
                {if $category.subcategories}
                    {include file="views/addons/components/addon_categories_tree.tpl"
                    categories_tree=$category.subcategories
                    direction=$direction
                    }
                {/if}
                <!--{$comb_id}--></li>
        {/if}
    {/foreach}
</ul>