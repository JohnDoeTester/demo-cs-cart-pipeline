{foreach $items as $k => $item}
    {if $id_field}
        {$id = $item.$id_field}
    {else}
        {$id = $k}
    {/if}
    
    {if $name_field}
        {$item_name = $item.$name_field}
    {else}
        {$item_name = $item}
    {/if}

    <label class="checkbox {if !$list_mode}inline{/if}" for="{$input_id}_{$id}">
        <input 
            type="checkbox" 
            name="{$input_name}[{$id}]" 
            id="{$input_id}_{$id}"
            value="{$id}"
            {if $id|in_array:$item_ids}
                checked="checked"
            {/if} 
        />
        {$item_name}
    </label>
{foreachelse}
    {__("no_items")}
{/foreach}