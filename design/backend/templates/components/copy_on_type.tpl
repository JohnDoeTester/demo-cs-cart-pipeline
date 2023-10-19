{*
    $source_value               string          required            Source input value
    $source_name                string          required            Source input name
    $target_value               string          required            Target input value
    $target_name                string          required            Target input name
    $source_label               string                              Source control group name
    $target_label               string                              Target control group name
    $type                       string                              Source and target type
    $source_id                  string                              Source unique ID
    $target_wrapper_id          string                              Target wrapper unique ID
    $target_id                  string                              Target unique ID
    $text_wrapper_id            string                              Text wrapper unique ID
    $text_id                    string                              Text unique ID
    $is_source_focus            bool                                Is source focus
    $required                   bool                                Whether to required on source input
*}

{script src="js/tygh/backend/copy_on_type.js"}

{$source_label      = $source_label|default:__("name")}
{$target_label      = $target_label|default:__("storefront_name")}
{$type              = $type|default:"name"}
{$source_id         = $source_id|default:"elm_source_`$type`_`$id`"}
{$target_wrapper_id = $target_wrapper_id|default:"elm_`$type`_target_wrapper_`$id`"}
{$target_id         = $target_id|default:"elm_`$type`_`$id`"}
{$text_wrapper_id   = $text_wrapper_id|default:"elm_`$type`_text_wrapper_`$id`"}
{$text_id           = $text_id|default:"elm_`$type`_text_`$id`"}
{$required          = $required|default:true}
{$is_same_value     = ($source_value === $target_value)}

<div class="control-group">
    <label class="control-label {if $required}cm-required{/if}" for="{$source_id}">{$source_label}</label>
    <div class="controls">
        <input id="{$source_id}"
            class="input-large"
            type="text"
            name="{$source_name}"
            value="{$source_value}"
            data-ca-copy-on-type-active="{$is_same_value}"
            data-ca-copy-on-type-target-selector="#{$target_id}"
            data-ca-copy-on-type-text-selector="#{$text_id}"
            {if $is_source_focus}
                autofocus
            {/if}
            />
        {if $is_same_value}
            <p id="{$text_wrapper_id}" class="muted description">
                <span class="copy-on-type__target-text-label">{$target_label}:</span>
                <span id="{$text_id}" class="copy-on-type__target-text">{$target_value}</span>
                <button type="button"
                    class="btn-link"
                    data-ca-copy-on-type-source-selector="#{$source_id}"
                    data-ca-copy-on-type-target-selector="#{$target_id}"
                    data-ca-copy-on-type-target-wrapper-selector="#{$target_wrapper_id}"
                    data-ca-copy-on-type-text-wrapper-selector="#{$text_wrapper_id}">
                    {__("edit")}
                </button>
            </p>
        {/if}
    </div>

</div>

<div id="{$target_wrapper_id}" class="control-group {if $is_same_value}hidden{/if}">
    <label class="control-label" for="{$target_id}">{$target_label}</label>
    <div class="controls">
        <input class="input-large" type="text" name="{$target_name}" value="{$target_value}" id="{$target_id}"/>
    </div>
</div>
