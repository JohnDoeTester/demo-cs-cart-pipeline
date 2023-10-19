<div class="object-selector-result-wrapper">
    <span class="object-selector-result object-selector-result--color">
        {$content_pre nofilter}
        <img class="object-selector-selection__image" src="{$append|default:"[append]"}" alt="{$text|default:"[text]"}" />
        <span class="object-selector-result__text">
            <span class="object-selector-result__prefix">{$prefix}</span> <span class="object-selector-result__body">{$text|default:"[text]"}</span>
        </span>
        {$content nofilter}
    </span>
    {if $help}
        <div class="object-selector-result__help object-selector-result__help--color">
            {__("enter_color_name_and_code")}
        </div>
    {/if}
</div>