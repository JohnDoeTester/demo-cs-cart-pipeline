{** block-description:product_bundles.template_product_bundles **}

{if $addons.product_bundles.show_bundles === "in_a_product_tab"}
    {$show_on_tab = false}

    {foreach $tabs as $tab}
        {if $tab.block_id === $block.block_id}
            {$show_on_tab = true}
            {$show_in_popup = ($tab.show_in_popup === "YesNo::YES"|enum)}
            {break}
        {/if}
    {/foreach}

    {script src="js/tygh/exceptions.js"}
    {component
        name="product_bundles.product_bundles"
        product_id=$smarty.request.product_id
        show_on_tab=$show_on_tab
        show_in_popup=$show_in_popup
        enable_padding=false
        show_on_products_page=true
    }{/component}
{/if}
