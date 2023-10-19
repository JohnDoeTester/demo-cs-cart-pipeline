{if $runtime.simple_ultimate}
	{capture name="mainbox"}
        <h4>{__("error_occured")}</h4>
        <p>{__("simple_ultimate_companies_selector") nofilter}</p>
	{/capture}
{else}
	{capture name="mainbox"}
        {$id = $select_id|default:"top_company_id"}

        <div class="store-selector js-storefront-switcher"
            data-ca-switcher-param-name="switch_company_id"
            data-ca-switcher-data-name="company_id">
            <div class="inline-label">{__("pick_store")} -</div>
            <div class="input-large inline-block">
                {include file="views/storefronts/components/picker/picker.tpl"
                    autoopen=true
                    show_advanced=false
                }
            </div>
        </div>
	{/capture}
{/if}

{include file="common/mainbox.tpl" title=__($title) content=$smarty.capture.mainbox}