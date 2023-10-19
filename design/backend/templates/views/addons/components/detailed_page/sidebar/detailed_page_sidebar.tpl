{hook name="addons_detailed:manage_sidebar"}

    {* Addon status *}
    {include file="views/addons/components/detailed_page/sidebar/addon_status.tpl"}

    {* Addon rating *}
    {include file="views/addons/components/detailed_page/sidebar/enjoy_addon.tpl"}

    {* CS-Cart Marketplace *}
    {include file="views/addons/components/detailed_page/sidebar/addon_market_info.tpl"}

    {* Support *}
    {include file="views/addons/components/detailed_page/sidebar/addon_support.tpl"}

{/hook}
