<div class="control-group setting-wide vendor-rating-recalculate">
    <label class="control-label">
        {__("vendor_rating.recalculate_rating")}
    </label>
    <div class="controls">
        <p>
            <a href="{fn_url("vendor_rating.recalculate")}" class="cm-ajax cm-post vendor-rating-recalculate__run" data-ca-target-id="rating">
                {__("vendor_rating.run_recalculation")}
            </a>
        </p>

        <div class="vendor-rating-recalculate__results" id="rating">
        <!--rating--></div>

        {include file="common/widget_copy.tpl"
            widget_copy_title=__("tip")
            widget_copy_text=__("vendor_rating.run_rating_calculation_by_cron")
            widget_copy_code_text = fn_get_console_command("php /path/to/cart/", $config.admin_index, [
                "dispatch" => "vendor_rating.recalculate",
                "p"
            ])
        }
    </div>
</div>
