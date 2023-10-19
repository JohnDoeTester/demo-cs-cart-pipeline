{$selected = $selected|default:"home"}

<div class="mockup__bottom-tabs bottomTabsBackgroundColor">
    <span class="mockup__bottom-tabs-btn">
        {$additional_icon_class = ($selected === "home") ? "bottomTabsSelectedIconColor" : "bottomTabsIconColor"}
        {include_ext file="common/icon.tpl" class="icon-home `$additional_icon_class`"}
        <div class="{if $selected === "home"}bottomTabsSelectedTextColor{else}bottomTabsTextColor{/if}">Home</div>
    </span>
    <span class="mockup__bottom-tabs-btn">
        {include_ext file="common/icon.tpl" class="fa fa-search fa-lg bottomTabsIconColor"}
        <div class="bottomTabsTextColor">Search</div>
    </span>
    <span class="mockup__bottom-tabs-btn">
        <span class="mockup__bottom-tabs-primary-badge bottomTabsPrimaryBadgeColor">2</span>
        {include_ext file="common/icon.tpl" class="icon-shopping-cart bottomTabsIconColor"}
        <div class="bottomTabsTextColor">Cart</div>
    </span>
    <span class="mockup__bottom-tabs-btn">
        {include_ext file="common/icon.tpl" class="icon-heart bottomTabsIconColor"}
        <div class="bottomTabsTextColor">Favorite</div>
    </span>
    <span class="mockup__bottom-tabs-btn">
        {$additional_icon_class = ($selected === "profile") ? "bottomTabsSelectedIconColor" : "bottomTabsIconColor"}
        {include_ext file="common/icon.tpl" class="icon-user `$additional_icon_class`"}
        <div class="{if $selected === "profile"}bottomTabsSelectedTextColor{else}bottomTabsTextColor{/if}">Profile</div>
    </span>
</div>
