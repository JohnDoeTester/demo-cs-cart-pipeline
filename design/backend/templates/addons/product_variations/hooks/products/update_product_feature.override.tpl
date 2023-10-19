{if $feature.product_variation_group}
    <div class="control-group">
        <label class="control-label" for="feature_{$feature_id}">
            <a href="{"product_features.update?feature_id={$feature_id}"|fn_url}">
                {$feature.internal_name}
            </a>
            <div>
                <small>
                    {$feature.description}
                </small>
            </div>
        </label>
        <div class="controls">
            {if $feature.prefix}<span>{$feature.prefix}</span>{/if}
            {foreach $feature.variants as $variant}
                {if $variant.selected}
                    <span class="shift-input">{$variant.variant}</span>
                {/if}
            {/foreach}
            {if $feature.suffix}<span>{$feature.suffix}</span>{/if}
            {include file="common/tooltip.tpl" tooltip=__("product_variations.feature_used_by_variation_group.tooltip", ["[code]" => $feature.product_variation_group.code])}
        </div>
    </div>
{/if}