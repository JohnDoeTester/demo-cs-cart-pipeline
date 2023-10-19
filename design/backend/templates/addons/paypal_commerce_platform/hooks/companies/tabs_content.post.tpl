<div class="hidden" id="content_paypal_commerce_platform">
    <div class="control-group">
        <label for="elm_paypal_commerce_platform_auth"
               class="control-label"
        >{__("paypal_commerce_platform.paypal_account")}:</label>
        <div class="controls">
            <input type="hidden"
                   name="company_data[paypal_commerce_platform_account_id]"
                   value="{$company_data.paypal_commerce_platform_account_id}"
            />
            <input type="hidden"
                   name="company_data[paypal_commerce_platform_email]"
                   value="{$company_data.paypal_commerce_platform_email}"
            />
            <p class="paypal-commerce-platform__account">
                {if $company_data.paypal_commerce_platform_account_id}
                    {$company_data.paypal_commerce_platform_account_id}
                {else}
                    {__("paypal_commerce_platform.not_connected")}
                {/if}
            </p>
        </div>
    </div>
    {if $company_data.company_id && ($paypal_commerce_platform_connect_url || $paypal_commerce_platform_disconnect_url)}
        <div class="control-group">
            <label class="control-label">&nbsp;</label>
            <div class="controls">
                {if $paypal_commerce_platform_connect_url}
                    <a class="btn btn-primary"
                       href="{$paypal_commerce_platform_connect_url}"
                    >{__("paypal_commerce_platform.connect_with_paypal")}</a>
                {/if}
                {if $paypal_commerce_platform_disconnect_url}
                    <a class="btn cm-post"
                       href="{$paypal_commerce_platform_disconnect_url}"
                    >{__("paypal_commerce_platform.disconnect")}</a>
                {/if}
            </div>
        </div>
    {/if}
</div>