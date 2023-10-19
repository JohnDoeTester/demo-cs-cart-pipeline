{$show_last_viewed_items = $show_last_viewed_items|default:true}
<div class="btn-group" id="last_edited_items">
    <a class="btn cm-back-link">{include_ext file="common/icon.tpl" class="icon-arrow-left"}</a>
    {if $show_last_viewed_items}
        <a class="btn dropdown-toggle" data-toggle="dropdown" href="#"><span class="caret"></span></a>
        <ul class="dropdown-menu">
        {if $breadcrumbs|sizeof >= 1}
            {foreach from=$breadcrumbs item="bc" name="bcn" key="key"}
                {if $bc.link}
                    <li><a href="{$bc.link|fn_url}">{$bc.title}</a></li>
                {else}
                    <li>{$bc.title}</li>
                {/if}
            {/foreach}
            <li class="divider"></li>
        {/if}
        {if $last_edited_items}
            {foreach from=$last_edited_items item=lnk}
                <li><a {if $lnk.icon}class="{$lnk.icon}"{/if} href="{$lnk.url|fn_url}" title="{$lnk.name|strip_tags}">{$lnk.name|strip_tags|truncate:40 nofilter}</a></li>
            {/foreach}
        {else}
            <li><a>{__("no_items")}</a></li>
        {/if}
        </ul>
    {/if}
<!--last_edited_items--></div>
