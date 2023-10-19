<div class="object-picker__product-feature-color object-picker__color" style="background-color: {literal}${data.color}{/literal}"></div>
<div>
    <div class="object-picker__product-feature-label">{$title_pre} {literal}${data.name}{/literal} {$title_post}</div>
    {if $help}
        <div class="object-picker__product-feature-help object-picker__product-feature-help--color">
            {__("enter_color_name_and_code")}
        </div>
    {/if}
</div>
