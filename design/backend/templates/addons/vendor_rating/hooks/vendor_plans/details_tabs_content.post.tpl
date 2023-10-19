<div id="content_rating_{$id}" class="hidden">
    <div class="control-group">
        <label for="elm_manual_rating" class="control-label">
            {__("vendor_rating.manual_vendor_plan_rating")}:
        </label>
        <div class="controls">
            <input type="text"
                   class="cm-numeric"
                   data-m-dec="0"
                   {if isset($manual_rating_criterion.range.min)}
                       data-v-min="{$manual_rating_criterion.range.min}"
                   {/if}
                   {if isset($manual_rating_criterion.range.max)}
                       data-v-max="{$manual_rating_criterion.range.max}"
                   {/if}
                   id="elm_manual_rating"
                   name="plan_data[manual_rating]"
                   value="{$plan.manual_rating|default:0}"
            />
        </div>
    </div>
<!--content_rating_{$id}--></div>
