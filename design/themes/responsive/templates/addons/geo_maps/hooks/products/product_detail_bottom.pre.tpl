{$show_shipping_estimation = $show_shipping_estimation|default:true}

{if $show_shipping_estimation}
    {include
        file = "addons/geo_maps/views/geo_maps/shipping_estimation.tpl"
        shipping_methods = null
        product_id = $shipping_estimation_product_id|default:null
    }
{/if}
