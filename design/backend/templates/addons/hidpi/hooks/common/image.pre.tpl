{if $image_data.is_thumbnail}
    {$width = $image_data.width * 2}
    {$height = $image_data.height * 2}
    {$image_data2x = $image|fn_image_to_display:$width:$height}
{elseif $image.icon.is_high_res}
    {$image_data2x = $image_data}
    {$image_data = $image|fn_image_to_display:$image.image_x:$image.image_y scope=parent}
{elseif $image.original_image_path}
    {$image_data2x = $image}
    {$image_data2x["image_path"] = $image.original_image_path}
{/if}
{if $image_data2x.image_path}
    {$image_attributes["srcset"] = "{$image_data2x.image_path} 2x" scope=parent}
{/if}
