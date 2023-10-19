{if !$runtime.company_id}
    <div id="content_rating" class="hidden">
        <div class="control-group">
            <label for="elm_manual_rating" class="control-label">
                {__("vendor_rating.manual_vendor_rating")}:
            </label>
            <div class="controls">
                <input type="text"
                       class="cm-numeric"
                       data-m-dec="0"
                       {if isset($rating_criterion.range.min)}
                           data-v-min="{$rating_criterion.range.min}"
                       {/if}
                       {if isset($rating_criterion.range.max)}
                           data-v-max="{$rating_criterion.range.max}"
                       {/if}
                       id="elm_manual_rating"
                       name="company_data[manual_vendor_rating]"
                       value="{$company_data.manual_vendor_rating|default:0}"
                />
            </div>
        </div>
        {if $company_data.company_id}
            <div class="control-group" id="vendor_rating">
                <label for="elm_rating" class="control-label">
                    {__("vendor_rating.absolute_vendor_rating")}:
                </label>
                <div class="controls">
                    <p>
                        {$company_data.absolute_vendor_rating} ({$company_data.relative_vendor_rating}%)
                    </p>
                    {if $company_data.absolute_vendor_rating_updated_timestamp}
                        <p>
                            {__("vendor_rating.calculated_at", [
                                "[date]" => $company_data.absolute_vendor_rating_updated_timestamp|date_format:"{$settings.Appearance.date_format}, {$settings.Appearance.time_format}"
                            ])}
                        </p>
                    {/if}
                    <p>
                        <a class="cm-ajax cm-post"
                           data-ca-target-id="vendor_rating"
                           href="{fn_url("vendor_rating.recalculate?company_id={$company_data.company_id}?redirect_url={fn_url($config.current_url)|escape:"url"}")}"
                        >{__("vendor_rating.recalculate_now")}</a>
                    </p>
                </div>
            <!--vendor_rating--></div>
        {/if}
    <!--content_rating--></div>
{/if}
