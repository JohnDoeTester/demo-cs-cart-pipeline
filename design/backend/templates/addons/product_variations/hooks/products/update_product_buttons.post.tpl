{if $product_data.variation_group_id}
    {include file="buttons/button.tpl" but_meta="hidden" but_id="tools_variations_btn" but_text=__("save") but_name="dispatch[products.m_update]" but_role="submit-link" but_target_form="manage_variation_products_form"}
{elseif !$is_form_readonly}
    {include file="buttons/button.tpl" but_meta="hidden" but_id="tools_variations_btn" but_text=__("save") but_name="dispatch[product_variations.add_product]" but_role="submit-link" but_target_form="manage_variation_products_form"}
{/if}

