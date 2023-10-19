{*
    $content            string                                      Content of field setting
    $extra              string                                      Content of extra field setting
    $global_value       int|string                                  Global value, is_global flag defines based on this value
    $is_global          boolean                         false       Is global
    $disable_input      boolean                         false       Disable input
    $html_id            string                                      HTML id
    $html_name          string                                      HTML name
    $related_field      object                                      Related field data
    $has_value_holder   boolean                                     Has value holder for disabled control
*}

{script src="js/tygh/backend/components/global_individual.js"}

<div class="global-individual"
    data-ca-global-individual="component"
    data-ca-global-individual-default-text="{__("default")}"
    data-ca-global-individual-html-id="{$html_id}"
    data-ca-global-individual-individual-html-name="{$item.individual_html_name}"
    data-ca-global-individual-global-html-name="{$item.global_html_name}"
    data-ca-global-individual-has-value-holder-for-disabled-control="{$has_value_holder}"
    data-ca-global-individual-is-global="{$item.has_global_value}"
>
    <input id="{$html_id}_empty_global_value"
        class="hidden"
        name="{$item.global_html_name}"
        value="{"\Tygh\Settings::NULL_VALUE"|constant}"
        data-ca-global-individual="hiddenInput">
    <div class="global-individual__field-wrapper"
        data-ca-global-individual="fieldWrapper"
    >
        {$content nofilter}
    </div>
    <div class="global-individual__buttons">
        <input
            type="checkbox"
            class="global-individual__btn cm-tooltip
                {if !$item.has_global_value}global-individual__btn--individual{/if}
                {if $disable_input}global-individual__btn--disabled{/if}"
            data-ca-global-individual="button"
            title="<div class='global-individual__tooltip'>
                    <span {if $item.has_global_value}class='hidden'{/if}
                        data-ca-global-individual-html-id='tooltip_global_{$html_id}'
                    >{__("global_individual.global_tooltip")}</span>
                    <span {if !$item.has_global_value}class='hidden'{/if}
                        data-ca-global-individual-html-id='tooltip_individual_{$html_id}'
                    >{__("global_individual.individual_tooltip")}</span>
                </div>
            "
        />
        {if $extra|trim !== ""}
            <div class="global-individual__container">
                {$extra nofilter}
            </div>
        {/if}
    </div>
</div>
