{script src="js/tygh/backend/widget_copy.js"}
<div class="cm-widget-copy widget-copy {$widget_copy_class}">
    {if $widget_copy_title || $widget_copy_text}
        <div class="widget-copy__body">
            {if $widget_copy_title}
                <strong class="widget-copy__title">{$widget_copy_title nofilter}.</strong>
            {/if}
            {if $widget_copy_text}
                <span class="widget-copy__text">{$widget_copy_text nofilter}</span>
            {/if}
        </div>
    {/if}
    {if $widget_copy_code_text}
        <div class="widget-copy__code">
            <button class="cm-widget-copy__btn widget-copy__btn" data-title="{__("copied")}" type="button">{__("copy")}</button>
            <pre class="widget-copy__pre"><code class="cm-widget-copy__code-text widget-copy__code-text">{$widget_copy_code_text}</code></pre>
        </div>
    {/if}
</div>