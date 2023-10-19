{foreach from=$product_features item=feature key="feature_id"}
    {include file="views/products/components/product_assign_feature.tpl" feature=$feature feature_id=$feature_id}
{/foreach}

{foreach from=$product_features item=feature key="feature_id"}
    {if $feature.feature_type == "ProductFeatures::GROUP"|enum && $feature.subfeatures}
        {include file="common/subheader.tpl" title=$feature.description additional_id=$feature.feature_id target="#acc_feature_`$feature.feature_id`"}
        <div id="acc_feature_{$feature.feature_id}" class="collapse in">
            {include file="views/products/components/product_assign_features.tpl" product_features=$feature.subfeatures}
        </div>
    {/if}
{/foreach}