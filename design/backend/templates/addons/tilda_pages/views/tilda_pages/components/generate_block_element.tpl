{$is_block_manager_available =
    $runtime.company_id
    || ("MULTIVENDOR"|fn_allowed_for)
}

{if $settings.Appearance.default_wysiwyg_editor === "redactor2" && $is_block_manager_available}
    <div class="control-group">
        <label class="control-label" for="elm_generate_block">{__("tilda_pages.generate_block")}:</label>
        <div class="controls">
            <textarea id="elm_generate_block"
                        cols="55"
                        rows="8"
                        class="cm-wysiwyg input-large"
                        data-ca-is-block-manager-enabled="{fn_check_view_permissions("block_manager.block_selection", "GET")|intval}"
                        data-ca-is-block-manager-available="{$is_block_manager_available}"
            ></textarea>

            <div class="well well-small help-block">
                {__("tilda_pages.generate_block_help", [
                    "[bm_icon]" => "<span class='icon-magic'></span>",
                    "[html_icon]" => "<span class='re-icon-html'></span>",
                    "[product]" => $smarty.const.PRODUCT_NAME
                ]) nofilter}
            </div>
        </div>
    </div>
{/if}