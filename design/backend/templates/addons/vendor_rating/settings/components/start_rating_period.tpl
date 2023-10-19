<div class="control-group setting-wide">
    <label for="elm_start_rating_period" class="control-label">
        {__("vendor_rating.start_rating_period")}
    </label>
    <div class="controls">
        {include file="common/calendar.tpl"
            date_id="elm_start_rating_period"
            date_name="addon_settings[start_rating_period]"
            date_val=$addons.vendor_rating.start_rating_period|default:0
            start_year=$settings.Company.company_start_year
        }
    </div>
</div>
