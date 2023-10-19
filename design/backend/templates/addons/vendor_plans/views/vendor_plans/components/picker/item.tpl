<div class="object-picker__vendor-plan-main">
    {if $type === "result"}
        <div class="object-picker__vendor-plan-content">
            <div class="object-picker__vendor-plan-name">
                {$title_pre} {literal}${data.name}{/literal}{$title_post}
            </div>
            <div class="object-picker__vendor-plan-monthly-fee">
                {literal}${data.price}{/literal}&nbsp;{literal}${data.periodicity}{/literal}
            </div>
            <div class="object-picker__vendor-plan-transaction-fee">
                {literal}${data.commission_text}{/literal}
            </div>
            <div class="object-picker__vendor-plan-vendor-count">
                {literal}${data.vendor_count_text}{/literal}
            </div>
            <div class="object-picker__vendor-plan-status">
                {literal}${data.status}{/literal}
            </div>
        </div>
    {elseif $type === "selection"}
        {literal}${data.name}{/literal}({literal}${data.price}{/literal})
    {elseif $type === "load"}
        ...
    {elseif $type === "new_item"}
        <div class="object-picker__results-label object-picker__vendor-plan-results-label">
            {if $icon|default:true}
                <div class="object-picker__results-label-icon-wrapper object-picker__vendor-plan-results-label-icon-wrapper">
                    {include_ext file="common/icon.tpl"
                        class="`$icon|default:'icon-plus-sign'` object-picker__results-label-icon object-picker__vendor-plan-results-label-icon"
                    }
                </div>
            {/if}
            {if $title_pre}
                <div class="object-picker__results-label-prefix object-picker__vendor-plan-results-label-prefix">
                    {$title_pre}
                </div>
            {/if}
            <div class="object-picker__results-label-body object-picker__vendor-plan-results-label-body">
                {literal}${data.name}{/literal}
            </div>
        </div>
    {/if}
</div>
