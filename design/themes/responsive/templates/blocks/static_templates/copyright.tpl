{** block-description:tmpl_copyright **}
<p class="bottom-copyright">
    &copy;
    {if $settings.Company.company_start_year && $smarty.const.TIME|date_format:"%Y" != $settings.Company.company_start_year}
        {$settings.Company.company_start_year} -
    {/if}
    
    {$smarty.const.TIME|date_format:"%Y"} {$settings.Company.company_name}. &nbsp;{__("powered_by")} <a class="bottom-copyright" href="{$config.resources.product_url|fn_link_attach:"utm_source=Powered+by&utm_medium=referral&utm_campaign=footer&utm_content=`$config.current_host`"}" target="_blank">{__("copyright_shopping_cart", ["[product]" => $smarty.const.PRODUCT_NAME])}</a>
</p>