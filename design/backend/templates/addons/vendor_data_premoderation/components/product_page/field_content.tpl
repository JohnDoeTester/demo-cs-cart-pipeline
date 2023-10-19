{$date_edited = $premoderation_data[$product_data.product_id].initial_timestamp|date_format:"`$settings.Appearance.date_format`"}

<div class="premoderation-fields__container">
    <div class="premoderation-fields__group">
        <div>
            {$original_content nofilter}
        </div>
    </div>
    {if $old_value !== false}
        <div class="control-group premoderation-fields__group premoderation-fields--old-value">
            <i class="icon icon-edit premoderation-fields__icon"></i>

            <div class="premoderation-fields__value-container">
                <label class="control-label premoderation-fields__value-label">{__("vendor_data_premoderation.prior_to_date", ['[date]' => $date_edited])}:</label>
                <div class="controls premoderation-fields__value-controls">
                    {$old_value nofilter}
                </div>
            </div>
        </div>
    {/if}
</div>
