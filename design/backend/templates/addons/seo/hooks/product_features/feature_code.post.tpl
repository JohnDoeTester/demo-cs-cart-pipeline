<div id="elm_feature_code_{$id}_description" class="muted description">
    <p>{__("seo.product_feature.feature_code.notice")}</p>
    {if $feature_codes}
        <ul>
            {foreach $feature_codes as $feature_code}
                <li>{$feature_code.name} - {$feature_code.description}</li>
            {/foreach}
        </ul>
    {/if}
</div>