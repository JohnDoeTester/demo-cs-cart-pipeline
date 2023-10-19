<div class="object-picker__results-label object-picker__options-results-label object-picker__results-label--new">
    {if $icon|default:true}
        <div class="object-picker__results-label-icon-wrapper object-picker__options-results-label-icon-wrapper object-picker__results-label-icon-wrapper--new">
            {include_ext file="common/icon.tpl"
                class="`$icon|default:'icon-plus-sign'` object-picker__results-label-icon object-picker__options-results-label-icon object-picker__results-label-icon--new"
            }
        </div>
    {/if}
    {if $title_pre}
        <div class="object-picker__results-label-prefix object-picker__options-results-label-prefix object-picker__results-label-prefix object-picker__options-results-label-prefix--new">
            {$title_pre}
        </div>
    {/if}
    <div class="object-picker__results-label-body object-picker__options-results-label-body object-picker__results-label-body--new">
        {literal}${data.name}{/literal}
    </div>
    {if $title_post}
        <div class="object-picker__results-label-suffix object-picker__options-results-label-suffix object-picker__results-label-suffix--new">
            {$title_post}
        </div>
    {/if}
</div>
