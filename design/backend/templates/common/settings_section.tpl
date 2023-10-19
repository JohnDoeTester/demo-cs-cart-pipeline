{foreach $items as $name => $item name="fe_data"}
    {$parent_item = []}
    {$parent_item_html_id = ""}

    {if $item.parent_id && $items[$item.parent_id]}
        {$parent_item = $items[$item.parent_id]}
        {$is_parent_global = isset($parent_item.global_data) && $parent_item.global_data.value !== ""}
        {$parent_item_html_id = "{$html_id_prefix}_{$section_name}_{if ($is_parent_global)}{$parent_item.global_data.name}_{$parent_item.global_data.object_id}{else}{$parent_item.name}_{$parent_item.object_id}{/if}"|strtolower}
    {/if}

    {$input_html_name = "{$html_name}[{$item.object_id}]"}
    {if isset($item.global_setting)}
        {$item.global_html_name = "{$html_name}[{$item.global_setting.object_id}]"}
        {$item.individual_html_name = $input_html_name}

        {if $item.has_global_value}
            {$input_html_name = $item.global_html_name}
        {/if}
    {/if}

    {include file="common/settings_fields.tpl"
        item=$item
        section_name=$section_name
        html_id="{$html_id_prefix}_{$section_name}_{$item.name}_{$item.object_id}"|strtolower
        html_name=$input_html_name
        index=$smarty.foreach.fe_data.iteration
        total=$smarty.foreach.fe_data.total
        class="`$class` `$item.class`"
        parent_item=$parent_item
        parent_item_html_id=$parent_item_html_id
    }
{/foreach}