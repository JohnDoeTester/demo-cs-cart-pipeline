{if $vendor_terms}
{foreach from=$vendor_terms item=vendor}
    <div class="ty-control-group ty-checkout__terms">
        <div class="cm-field-container">
            {strip}
                <label for="product_agreements_{$suffix}_{$vendor.company_id}" class="cm-check-agreement checkbox">
                    <input type="checkbox" id="product_agreements_{$suffix}_{$vendor.company_id}" name="agreements[]" value="Y" class="cm-agreement checkbox"  {if $iframe_mode}onclick="fn_check_agreements('{$suffix}');"{/if}/>
                    {capture name="vendor_terms_href"}
                        <a id="sw_elm_vendor_terms_{$suffix}_{$vendor.company_id}" class="cm-combination ty-dashed-link">
                            {__("vendor_terms.checkout_terms_and_conditions_name")}
                        </a>
                    {/capture}
                    <span>{__("vendor_terms.checkout_terms_and_conditions", ["[vendor]" => $vendor.company, "[terms_href]" => $smarty.capture.vendor_terms_href]) nofilter}</span>
                </label>
            {/strip}
        </div>
        <div class="hidden" id="elm_vendor_terms_{$suffix}_{$vendor.company_id}">
            {$vendor.terms nofilter}
        </div>
    </div>
{/foreach}
{/if}
