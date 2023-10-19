{** block-description:blog.text_links **}

{$parent_id=$block.content.items.parent_page_id}
{if $items}
    <div class="ty-blog-text-links">
        <ul>
            {foreach from=$items item="page" name="fe_blog"}
                <li class="ty-blog-text-links__item">
                    <div class="ty-blog-text-links__date">{$page.timestamp|date_format:$settings.Appearance.date_format}</div>
                    <a href="{"pages.view?page_id=`$page.page_id`"|fn_url}" class="ty-blog-text-links__a">{$page.page}</a>
                </li>
            {/foreach}
        </ul>

        {if $parent_id}
        <div class="ty-mtb-s ty-uppercase">
            <a href="{"pages.view?page_id=`$parent_id`"|fn_url}">{__("view_all")}</a>
        </div>
        {/if}
    </div>
{/if}