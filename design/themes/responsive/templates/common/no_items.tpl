{if !$reset_url}
    {$reset_url = $config.current_url}
{/if}

{if $type}
    {$text_no_found = __("object_not_found", ["[object]" => __($type)])}
{elseif !$text_no_found}
    {$text_no_found = __("text_nothing_found")}
{/if}

<div class="ty-no-items cm-pagination-container {if $no_items_extended}ty-no-items--extended{/if} {$no_items_meta}">
    {$text_no_found nofilter}
    {if $no_items_extended}
        <a href="{$reset_url|fn_url}" rel="nofollow" class="ty-btn ty-mt-s {$reset_meta}">{__("reset")}</a>
    {/if}
</div>
