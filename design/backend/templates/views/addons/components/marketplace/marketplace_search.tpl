<div class="sidebar-row addons-marketplace-search">
    <form action="{""|fn_url}"
        name="addons_marketplace-search_form" 
        method="get"
        class="form--no-margin"
        data-ca-addons-marketplace="marketplaceSearchForm"
    >
        <input type="hidden" name="result_ids" value="addons_market,actions_panel"/>

        <div class="controls">
            <input type="text"
                name="q"
                value="{$search.q}"
                autofocus
                class="input-full input--no-margin"
                placeholder="{__("cscart_marketplace.search")}"
                data-ca-addons-marketplace="marketplaceSearch"
            />
        </div>

        {include file="buttons/search.tpl" but_name="dispatch[addons.market]" but_meta="hidden"}
    </form>
    <div class="muted description">
        {__("cscart_marketplace.search_description")}
    </div>
</div>
