{strip}
{foreach $items as $category}
<li class="{if $separated}b-border {/if}{if $category.subcategories}dir{/if}">
    {if $category.subcategories}
        {include_ext file="common/icon.tpl" class="ty-icon-right-open"}
        {include_ext file="common/icon.tpl" class="ty-icon-left-open"}
        <div class="hide-border">&nbsp;</div>
        <ul>
            {include file="views/categories/components/menu_items.tpl" items=$category.subcategories separated=true submenu=true cid=$category.category_id}
        </ul>
    {/if}
    <a href="{"categories.view?category_id=`$category.category_id`"|fn_url}">{$category.category}</a>
</li>
{/foreach}
{/strip}
