{** block-description:text_links **}

{if $block.properties.show_items_in_line == 'Y'}
    {assign var="inline" value=true}
{/if}

{assign var="text_links_id" value=$block.snapping_id}

{if $items}
    {if $inline && !$submenu}
    <div class="ty-text-links-wrapper">
        <span id="sw_text_links_{$text_links_id}" class="ty-text-links-btn cm-combination visible-phone">
            {include_ext file="common/icon.tpl"
                class="ty-icon-short-list"
            }
            {include_ext file="common/icon.tpl"
                class="ty-icon-down-micro ty-text-links-btn__arrow"
            }
        </span>
    {/if}

        {strip}
            <ul {if !$submenu}id="text_links_{$text_links_id}"{/if} {""}
                class="ty-text-links {""}
                    {if $inline && !$submenu} {""}
                        cm-popup-box ty-text-links_show_inline
                    {/if}
            ">
                {foreach from=$items item="menu"}
                    <li class="ty-text-links__item {""}
                        ty-level-{$menu.level|default:0} {""}
                        {if $menu.active} {""}
                            ty-text-links__active
                        {/if}
                        {if $inline && !$submenu && $menu.subitems} {""}
                            ty-text-links__subitems
                        {/if}
                        {if $inline && !$submenu} {""}
                            ty-text-links__item--inline
                        {/if}
                        {if $inline && $submenu} {""}
                            ty-text-links__item--submenu
                        {/if}
                        {if $menu.class} {""}
                            {$menu.class}
                        {/if}
                    ">
                        <a class="ty-text-links__a {""}
                            ty-text-links__a--level-{$menu.level|default:0} {""}
                            {if $inline && !$submenu && $menu.subitems} {""}
                                ty-text-links__a--subitems
                            {/if}
                            {if $inline && !$submenu} {""}
                                ty-text-links__a--inline
                            {/if}
                            {if $inline && $submenu} {""}
                                ty-text-links__a--submenu
                            {/if}
                            {if $menu.active} {""}
                                ty-text-links__a--active
                            {/if}"
                            {if $menu.href} {""}
                                href="{$menu.href|fn_url}"
                            {/if}
                            {if $menu.new_window} {""}
                                target="_blank"
                            {/if}
                        >
                            {$menu.item}
                        </a>
                        {if $menu.subitems}
                            {include file="blocks/menu/text_links.tpl" items=$menu.subitems submenu=true}
                        {/if}
                    </li>
                {/foreach}
            </ul>
        {/strip}

    {if $inline && !$submenu}
    </div>
    {/if}
{/if}