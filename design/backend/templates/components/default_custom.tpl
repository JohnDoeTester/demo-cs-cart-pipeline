{*
    $variants                      array           required            Select data
    $value                         string                              Select value
    $name                          string                              Select name
    $component_id                  string                              Select ID
    $show_custom                   string                              Show custom
*}

{script src="js/tygh/backend/components/default_custom.js"}

{$name = $name|default:"default_custom"}
{$selectbox = []}
{$item_map = []}
{$selected = []}
{foreach $variants as $variant}
    {$variant.selected = ($variant.value === $value)}
    {$item_map[$variant.type][] = $variant}
    {if $variant.selected}
        {$selected = $variant}
    {/if}
{/foreach}
{if !$selected}
    {$item_map.custom[] = [
        "type"     => "custom",
        "value"    => $value,
        "name"     => __("default_custom.custom_value", ["value" => $value]),
        "selected" => true
    ]}
{/if}
{if $show_custom}
    {$item_map.show_custom[] = [
        "type" => "custom_edit",
        "value" => null,
        "name"  => __("default_custom.custom")
    ]}
{/if}

<div class="default-custom"
    data-ca-default-custom="main"
    data-ca-default-custom-selected-type="{$selected.type}"
    data-ca-default-custom-selected-value="{$selected.value}"
>
    <select name="{$name}"
        id="{$component_id}"
        data-ca-default-custom="select"
        {if $disable_inputs}disabled="disabled"{/if}
    >
        {$items_count = 0}
        {foreach ["disabled", "inheritance", "custom", "variant", "show_custom", "inheritance_edit"] as $type}
            {if ($type === "custom" && $items_count > 0) || ($type === "inheritance_edit" && !empty($item_map[$type]))}
                <option disabled>─────────────</option>
            {/if}
            {if !$item_map[$type]}
                {continue}
            {/if}
            {$items = $item_map[$type]}

            {foreach $items as $item}
                <option data-ca-default-custom="{$item.type}"
                    {if $item.url}data-ca-default-custom-url="{$item.url|fn_url}"{/if}
                    {if $item.value}value="{$item.value}"{/if}
                    {if $item.selected}selected{/if}
                >
                    {$item.name}
                </option>

                {$items_count = $items_count + 1}
            {/foreach}
        {/foreach}
    </select>
    {if $show_custom}
        <input type="text"
            name="{$name}"
            value="{$items.custom.value}"
            class="{$custom_input_styles} hidden"
            {if $custom_input_attributes}
                {foreach $custom_input_attributes as $data_name => $data_value}
                    {if isset($data_value)}
                        {$data_name}="{$data_value}"
                    {/if}
                {/foreach}
            {/if}
            disabled
            data-ca-default-custom="textbox"
        >
    {/if}
</div>
