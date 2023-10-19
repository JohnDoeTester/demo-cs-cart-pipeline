{if $select_languages|default:(bool) $id && $languages|sizeof > 1}{strip}
    {$simple_tooltip = $simple_tooltip|default:false}
    {$hide_inputs = $hide_inputs|default:false}
    {$image_hide_inputs_class = ($hide_inputs) ? "cs-append-language__icon--hide-inputs cm-tooltip" : ""}
    {if !$simple_tooltip}
        {include_ext file="common/icon.tpl"
            class="flag flag-{$languages[$smarty.const.DESCR_SL].country_code|lower}"
            assign="flag"
        }
    {/if}
    <span class="add-on cs-append-language cm-field-suffix cm-tooltip" {""}
        title="{$flag}{$languages[$smarty.const.DESCR_SL].name}" {""}
    >
        <img src="{$images_dir}/append_language.svg" {""}
            alt="{$languages[$smarty.const.DESCR_SL].name}" {""}
            class="cs-append-language__icon {$image_hide_inputs_class}" {""}
            {if $hide_inputs}
                title="{$flag}{$languages[$smarty.const.DESCR_SL].name}" {""}
            {/if}
        />
    </span>
{/strip}{/if}