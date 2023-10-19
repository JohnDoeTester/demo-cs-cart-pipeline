{if $actual_change_log && $actual_change_log.compatibility !== true}
    <div class="alert alert-block">
        <h4>
            {__("requires_upgrade", ["[product]" => $smarty.const.PRODUCT_NAME])}
        </h4>

        <p>
            {__("new_add_on_is_not_compatible_with_your_product", [
                "[product]" => $smarty.const.PRODUCT_NAME,
                "[version]" => $actual_change_log.compatibility
            ])}
        </p>
        <p>
            {include file="buttons/button.tpl"
                but_href="upgrade_center.manage"
                but_role="action"
                but_meta="btn-primary"
                but_text=__("upgrade_and_update_addon", ["[product]" => $smarty.const.PRODUCT_NAME])
            }
        </p>
    </div>
{/if}
