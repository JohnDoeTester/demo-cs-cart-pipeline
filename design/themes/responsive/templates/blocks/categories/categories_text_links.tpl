{** block-description:text_links **}

{if $items}
<ul class="ty-text-links">
    {foreach from=$items item="category"}
    <li class="ty-text-links__item ty-level-{$category.level|default:0}{if $category.active || $category|fn_check_is_active_menu_item:$block.type} ty-text-links__active{/if}">
        <a class="ty-text-links__a"
           href="{$category|fn_form_dropdown_object_link:$block.type}"
        >
            {$category.category}
        </a>
    </li>
    {/foreach}
</ul>
{/if}