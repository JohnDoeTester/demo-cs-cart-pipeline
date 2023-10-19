{if $runtime.company_id}
    {include file="addons/master_products/views/components/manage_vendor.tpl"}
{else}
    {include
        file="views/products/manage.tpl"
        show_stock_control_in_bulk_edit=false
        show_bulk_edit_items_product_approval_control=false
        show_bulk_edit_actions=true
        show_bulk_edit_prices_block_title=true
        show_common_products_action_buttons=true
        show_list_quantity=true
        dispatch="products.master_products"
        delete_redirect_url="products.master_products"
    }
{/if}