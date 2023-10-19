{if $image_data.is_thumbnail}
    {$width = $image_data.width * 2}
    {$height = $image_data.height * 2}
    {$image_data2x = $images|fn_image_to_display:$width:$height}
{elseif $images.icon.is_high_res}
    {$image_data2x = $image_data}
    {$image_data = $images|fn_image_to_display:$images.icon.image_x:$images.icon.image_y scope=parent}
{elseif $images.original_image_path}
    {$image_data2x = $images}
    {$image_data2x["image_path"] = $images.original_image_path}
{/if}
{if $image_data2x.image_path}
    {if $lazy_load}
        {$image_additional_attrs["data-srcset"] = "{$image_data2x.image_path} 2x" scope=parent}
    {else}
        {$image_additional_attrs["srcset"] = "{$image_data2x.image_path} 2x" scope=parent}
    {/if}
{/if}
