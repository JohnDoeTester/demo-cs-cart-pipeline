{strip}
    {if isset($vendor_data_premoderation.require_approval_count) || isset($vendor_data_premoderation.disapproved_count)}
        {foreach $analytics_data as $analytics_column_key => $analytics_column}
            {foreach $analytics_column as $analytics_card_key => $analytics_card}
                {if $analytics_card.id === "analytics_card_products"}
                    {if isset($vendor_data_premoderation.require_approval_count)}
                        {$analytics_data[$analytics_column_key][$analytics_card_key].resource_list.content[] = [
                            id => "vendor_data_premoderation_analytics_card_products_out_of_stock",
                            name => __("vendor_data_premoderation.dashboard.analytics_card.on_moderation"),
                            href => "products.manage?status={"Addons\VendorDataPremoderation\ProductStatuses::REQUIRES_APPROVAL"|enum}",
                            value => $vendor_data_premoderation.require_approval_count|number_format,
                            value_href => "products.manage?status={"Addons\VendorDataPremoderation\ProductStatuses::REQUIRES_APPROVAL"|enum}"
                        ]}
                    {/if}
                    {if isset($vendor_data_premoderation.disapproved_count)}
                        {$analytics_data[$analytics_column_key][$analytics_card_key].resource_list.content[] = [
                            id => "vendor_data_premoderation_analytics_card_products_disapproved",
                            name => __("vendor_data_premoderation.dashboard.analytics_card.disapproved"),
                            href => "products.manage?status={"Addons\VendorDataPremoderation\ProductStatuses::DISAPPROVED"|enum}",
                            value => $vendor_data_premoderation.disapproved_count|number_format,
                            value_href => "products.manage?status={"Addons\VendorDataPremoderation\ProductStatuses::DISAPPROVED"|enum}"
                        ]}
                    {/if}
                {/if}
            {/foreach}
        {/foreach}
        {$analytics_data = $analytics_data scope=parent}
    {/if}
{/strip}