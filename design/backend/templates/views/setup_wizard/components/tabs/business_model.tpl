{$available_business_models = fn_setup_wizard_get_available_business_models()}
{$current_business_model = fn_setup_wizard_get_current_business_model()}

<div class="sw-right-block">
    {include file="buttons/button.tpl" but_href="payments.manage" but_text=__("sw.configure") but_role="action" but_target="_blank"}
</div>
<div class="sw-columns-block">
    <div id="sw_money_transfer_from">
        <form name="sw_money_transfer_from" class="form-horizontal cm-ajax cm-ajax-force" action="{""|fn_url}" method="post">
            <input type="hidden" name="dispatch" value="setup_wizard.change_money_transfer" />
            <input type="hidden" name="result_ids" value="sw_money_transfer_from" />

            <div class="control-group">
                <h2 class="sw-block-title">{__("sw.select_money_transfer_methods")}</h2>
            </div>
            {foreach $available_business_models as $business_model_id => $business_model_data}
                <div class="sw-columns-block-line">
                    <div class="control-group">
                        <label class="control-label control-label-radio">
                            <input type="radio"
                                   name="money_transfer_type"
                                   id="radio_{$business_model_id}"
                                   class="cm-submit ladda-button"
                                   data-ca-target-form="sw_money_transfer_from"
                                   value="{$business_model_id}"
                                   {if $current_business_model === $business_model_id}checked{/if}
                            />
                            {$business_model_data.name}
                            <p>
                                {$business_model_data.description}
                            </p>
                        </label>
                    </div>
                </div>
            {/foreach}
        </form>
    <!--sw_money_transfer_from--></div>
</div>
