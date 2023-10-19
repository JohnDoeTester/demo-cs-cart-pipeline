{strip}
{*
    Import
    ---
    $bundle

    Export
    ---
    $item_quantity
    $item_quantity_responsive
    $scroller_item_attrs
    $bundle_block

    Global
    ---
    $bundle_count
    $item_quantity
    $item_quantity_responsive

    Local
    ---
    $bundle_product
*}

{* Item quantity *}
{$bundle_count = $bundle.products|@count}

{if $bundle_count <= 4}
    {$item_quantity = $bundle_count}
{/if}

{$item_quantity = $item_quantity|default:4}

{* Item quantity responsive *}
{$item_quantity_responsive = [
    "desktop"       => $item_quantity,
    "desktop_small" => $item_quantity - 1,
    "tablet"        => $item_quantity - 1,
    "mobile"        => 1
]}
{if $item_quantity > 3}
    {$item_quantity_responsive["desktop_small"] = $item_quantity - 1}
    {$item_quantity_responsive["tablet"]        = $item_quantity - 2}
{elseif $item_quantity === 1}
    {$item_quantity_responsive["desktop_small"] = $item_quantity}
    {$item_quantity_responsive["tablet"]        = $item_quantity}
    {$item_quantity_responsive["mobile"]        = $item_quantity}
{/if}

{* Scroller attributes *}
{$scroller_item_attrs = [
    "data-ca-scroller-item"                 => $item_quantity,
    "data-ca-scroller-item-desktop"         => $item_quantity_responsive["desktop"],
    "data-ca-scroller-item-desktop-small"   => $item_quantity_responsive["desktop_small"],
    "data-ca-scroller-item-tablet"          => $item_quantity_responsive["tablet"],
    "data-ca-scroller-item-mobile"          => $item_quantity_responsive["mobile"]
]}

{* Block properties *}
{$bundle_block = [
    block_id => "product_bundle_`$bundle.bundle_id`",
        properties => [
            item_quantity => $item_quantity,
            not_scroll_automatically => "YesNo::YES"|enum,
            outside_navigation => true
        ]
    ]
}

{* Export *}
{$item_quantity             = $item_quantity            scope="parent"}
{$item_quantity_responsive  = $item_quantity_responsive scope="parent"}
{$scroller_item_attrs       = $scroller_item_attrs      scope="parent"}
{$bundle_block              = $bundle_block             scope="parent"}
{/strip}