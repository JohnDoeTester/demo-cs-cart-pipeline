{foreach $product_features as $feature}
    {if $feature.feature_type != "ProductFeatures::GROUP"|enum}
        {include_ext file="common/icon.tpl" class="ty-icon-help-circle" assign=link_text_icon}
        <div class="ty-product-feature">
        <div class="ty-product-feature__label">{$feature.description nofilter}{if $feature.full_description|trim}{include file="common/help.tpl" text=$feature.description content=$feature.full_description id=$feature.feature_id show_brackets=false link_text="<span class=\"ty-tooltip-block\">`$link_text_icon`</span>" wysiwyg=true}{/if}:</div>

        {$hide_affix = $feature.feature_type == "ProductFeatures::MULTIPLE_CHECKBOX"|enum}

        {strip}
        <div class="ty-product-feature__value">
            {if $feature.prefix && !$hide_affix}<span class="ty-product-feature__prefix">{$feature.prefix}</span>{/if}
            {if $feature.feature_type == "ProductFeatures::SINGLE_CHECKBOX"|enum}
            <span class="ty-compare-checkbox">{if $feature.value === "YesNo::YES"|enum}{include_ext file="common/icon.tpl" class="ty-icon-ok ty-compare-checkbox__icon"}{/if}
            </span>
            {elseif $feature.feature_type == "ProductFeatures::DATE"|enum}
                {$feature.value_int|date_format:"`$settings.Appearance.date_format`"}
            {elseif $feature.feature_type == "ProductFeatures::MULTIPLE_CHECKBOX"|enum && $feature.variants}
                <ul class="ty-product-feature__multiple">
                {foreach from=$feature.variants item="var"}
                    {$hide_variant_affix = !$hide_affix}
                    {if $var.selected}<li class="ty-product-feature__multiple-item"><span class="ty-compare-checkbox">{include_ext file="common/icon.tpl" class="ty-icon-ok ty-compare-checkbox__icon"}</span>{if !$hide_variant_affix}<span class="ty-product-feature__prefix">{$feature.prefix}</span>{/if}{$var.variant}{if !$hide_variant_affix}<span class="ty-product-feature__suffix">{$feature.suffix}</span>{/if}</li>{/if}
                {/foreach}
                </ul>
            {elseif in_array($feature.feature_type, ["ProductFeatures::TEXT_SELECTBOX"|enum, "ProductFeatures::EXTENDED"|enum, "ProductFeatures::NUMBER_SELECTBOX"|enum])}
                {foreach $feature.variants as $variant}
                    {if $variant.selected}{$variant.variant}{break}{/if}
                {/foreach}
            {elseif $feature.feature_type == "ProductFeatures::NUMBER_FIELD"|enum}
                {$feature.value_int|floatval|default:"-"}
            {else}
                {$feature.value|default:"-"}
            {/if}
            {if $feature.suffix && !$hide_affix}<span class="ty-product-feature__suffix">{$feature.suffix}</span>{/if}
        </div>
        {/strip}
        </div>
    {/if}
{/foreach}

{foreach $product_features as $feature}
    {if $feature.feature_type == "ProductFeatures::GROUP"|enum && $feature.subfeatures}
        <div class="ty-product-feature-group">
        {include file="common/subheader.tpl" title=$feature.description tooltip=$feature.full_description text=$feature.description}
        {include file="views/products/components/product_features.tpl" product_features=$feature.subfeatures}
        </div>
    {/if}
{/foreach}
