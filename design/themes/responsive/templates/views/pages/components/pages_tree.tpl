{foreach $tree as $key => $page}
    {if $page.page_id == $smarty.request.page_id}{assign var="path" value=$page.id_path}{/if}
{/foreach}

{foreach $tree as $key => $page}
    {math equation="x*10" x=$page.level assign="shift"}
    <li>
        <a href="{if $page.page_type == $smarty.const.PAGE_TYPE_LINK}{$page.link|fn_url}{else}{"pages.view?page_id=`$page.page_id`"|fn_url}{/if}"{if $page.new_window} target="_blank"{/if}{if $page.level != "0"} style="padding-{$direction}: {$shift}px;"{/if}>{$page.page}</a>
    </li>
{/foreach}
