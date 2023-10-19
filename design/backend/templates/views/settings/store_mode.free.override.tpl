<div class="hidden" title="{__("store_mode")}" id="store_mode_dialog">
    <div class="store-mode free-mode">
        {__("product_state_description.`$product_state_suffix`")}

        {if $store_mode_license}
            {__("license_number")}: {$store_mode_license}
        {/if}

        <div class="center">
            <a class="btn btn-primary btn-large" href="{$config.resources.helpdesk_url}" target="_blank">{__("contact_us")}</a>
        </div>
    </div>
</div>
