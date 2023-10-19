<div id="content_tab_link_existing_{$product_data.product_id}">
    {include file="addons/product_variations/views/product_variations/components/search_product_list.tpl"}
<!--content_tab_link_existing_{$product_data.product_id}--></div>

<div id="tools_tab_link_existing_{$product_data.product_id}">
    {if $products}
        {include file="buttons/button.tpl" but_text=__("product_variations.add_variations") but_role="submit-link" but_name="dispatch[product_variations.link]" but_meta="btn-primary" but_target_form="add_product_to_group_form"}
    {/if}
<!--tools_tab_link_existing_{$product_data.product_id}--></div>