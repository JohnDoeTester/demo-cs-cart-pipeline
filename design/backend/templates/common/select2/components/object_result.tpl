<div class="object-selector-result-wrapper">
    <span class="object-selector-result">
        {if $icon}
            <span class="object-selector-result__icon-wrapper">
                {include_ext file="common/icon.tpl"
                    class="`$icon` object-selector-result__icon"
                }
            </span>
        {/if}
        {$content_pre nofilter}
        <span class="object-selector-result__text"><span class="object-selector-result__prefix">{$prefix}</span> <span class="object-selector-result__body">[text]</span></span>
        <span class="object-selector-result__append">[append]</span>
        {$content nofilter}
    </span>
    {if $help}
        <div class="object-selector-result__help">
            {$help}
        </div>
    {/if}
</div>
