{if $product_data.variation_features}
    {* Variation features are always displayed in the title *}

    {* Approximate desktop header width *}
    {$heading_length = 40}

    {capture name="variation_features_title"}{include file="addons/product_variations/views/product_variations/components/variation_features.tpl"
            variation_features=$product_data.variation_features
            features_tags=false
    }{/capture}

    {$product_name_length = $heading_length - $smarty.capture.variation_features_title|count_characters:true}
    {$product_name = $product_data.product|strip_tags|truncate:$product_name_length:"...":true}
    {$title_end = "`$product_name` — `$smarty.capture.variation_features_title`" scope=parent}

    {$title_alt = "`$product_data.product|strip_tags` — `$smarty.capture.variation_features_title`" scope=parent}
{/if}

{script src="js/addons/product_variations/tygh/backend/func.js"}
