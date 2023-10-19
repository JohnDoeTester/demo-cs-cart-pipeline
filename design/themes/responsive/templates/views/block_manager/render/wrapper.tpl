{if $runtime.customization_mode.block_manager && $location_data.is_frontend_editing_allowed}
    {include file="backend:views/block_manager/frontend_render/wrapper.tpl"}
{elseif $content|trim}
    {include file=$wrapper}
{/if}