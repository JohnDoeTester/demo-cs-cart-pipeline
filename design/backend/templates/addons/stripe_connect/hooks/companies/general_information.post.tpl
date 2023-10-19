{include file="common/subheader.tpl" title=__("stripe_connect.stripe_connect")}
<div class="control-group">
    <label for="elm_stripe_connect_auth"
           class="control-label"
    >{__("stripe_connect.stripe_account")}:</label>
    <div class="controls">
        <input type="hidden"
               name="company_data[stripe_connect_account_id]"
               value="{$company_data.stripe_connect_account_id}"
        />
        {if $company_data.stripe_connect_account_id}
            <p class="text-success">{$company_data.stripe_connect_account_id}</p>
        {elseif $stripe_express_continue_registration_url}
            <p class="text-error">{__("stripe_connect.registration_is_not_complete")}</p>
        {else}
            <p>{__("stripe_connect.not_connected")}</p>
        {/if}
    </div>
</div>
{if $company_data.company_id && $runtime.company_id && ($stripe_express_connect_url || $stripe_standard_connect_url || $stripe_disconnect_url || $stripe_express_continue_registration_url)}
    <div class="control-group">
        <label class="control-label">&nbsp;</label>
        <div class="controls">
            {if $stripe_express_connect_url || $stripe_express_continue_registration_url}
                {if $stripe_express_continue_registration_url}
                    <a class="btn btn-primary"
                       href="{$stripe_express_continue_registration_url}"
                    >{__("stripe_connect.continue_express_registration")}</a>
                {elseif $stripe_express_connect_url}
                    <a class="btn btn-primary"
                       href="{$stripe_express_connect_url}"
                    >{__("stripe_connect.connect_stripe_express_account")}</a>
                {/if}
                {if $stripe_standard_connect_url}
                    <a class="btn"
                       href="{$stripe_standard_connect_url}"
                    >{__("stripe_connect.connect_stripe_standard_account")}</a>
                {/if}
            {else}
                {if $stripe_standard_create_url}
                    <a class="btn"
                       href="{$stripe_standard_create_url}"
                    >{__("stripe_connect.create_stripe_standard_account")}</a>
                {/if}
                {if $stripe_standard_connect_url}
                    <a class="btn btn-primary"
                       href="{$stripe_standard_connect_url}"
                    >{__("stripe_connect.connect_stripe_standard_account")}</a>
                {/if}
            {/if}

            {if $stripe_disconnect_url}
                <a class="btn cm-post"
                   href="{$stripe_disconnect_url}"
                >{__("stripe_connect.disconnect")}</a>
            {/if}
        </div>
    </div>
{/if}
