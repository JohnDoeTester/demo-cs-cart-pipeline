{script src="js/tygh/backend/addons/market.js"}
{$page_title = ($search.q) ? "{__("addon_market")}: `$search.q`" : __("addon_market")}

{capture name="mainbox"}
<div class="items-container" id="addons_market">
    {include file="views/addons/components/addons_list.tpl"
        is_marketplace_addons=true
    }
<!--addons_market--></div>

{/capture}
{include file="common/mainbox.tpl"
    title=$page_title
    content=$smarty.capture.mainbox
    sidebar=({include file="views/addons/components/marketplace/marketplace_sidebar.tpl"})
    adv_buttons=({include file="views/addons/components/manage/manage_adv_buttons.tpl"})
    select_storefront=true
    show_all_storefront=true
    storefront_switcher_param_name="storefront_id"
}
