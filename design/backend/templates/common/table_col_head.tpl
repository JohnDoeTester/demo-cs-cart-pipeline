{strip}
{*
    Sort link for table column
    ---
    $type
    $text
    $title
    $c_url
    $search
    $rev
*}

{$c_url = ($c_url) ? $c_url : $config.current_url|fn_query_remove:"sort_by":"sort_order"}
{$rev = ($rev) ? $rev : $smarty.request.content_id|default:"pagination_contents"}

{if $type}
    <a class="cm-ajax th-text-overflow {if $type === $search.sort_by}th-text-overflow--{$search.sort_order_rev}{/if} {$class}" {""}
        href="{"`$c_url`&sort_by=`$type`&sort_order=`$search.sort_order_rev`"|fn_url}"
        {if $rev}
            {""} data-ca-target-id={$rev}
        {/if} {if $title}
            {""} title="{$title}"
        {/if}
    >
        {$text|default:__($type) nofilter}
    </a>
{elseif $text}
    <span class="th-text-overflow"
        {if $title}{""} title="{$title}"{/if}
    >
        {$text nofilter}
    </span>
{/if}
{/strip}