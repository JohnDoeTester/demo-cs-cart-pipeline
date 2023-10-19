{$_max_desktop_items = $max_desktop_items|default:5}

<div class="litecheckout__item ty-checkout-select-store__map-full-div pickup pickup--list">

    {* For mobiles; List wrapper with selected pickup item *}
    {foreach from=$shipping.data.stores item=store}
        {if $old_store_id == $store.store_location_id}
        <div class="ty-checkout-select-store pickup__offices-wrapper visible-phone pickup__offices-wrapper--near-map">
            {* List *}
            <div class="litecheckout__fields-row litecheckout__fields-row--wrapped pickup__offices pickup__offices--list pickup__offices--list-no-height">
                {include file="addons/store_locator/views/checkout/components/shippings/items/pickup.tpl" store=$store}
            </div>
            {* End of List *}
        </div>
        {/if}
    {/foreach}
    {* For mobiles; List wrapper with selected pickup item *}

    {* For mobiles; button for popup with pickup points *}
    <button class="ty-btn ty-btn__secondary cm-open-pickups pickup__open-pickupups-btn visible-phone"
        data-ca-title="{__('lite_checkout.choose_from_list')}"
        data-ca-target=".pickup__offices-wrapper-open"
        type="button"
    >{__('lite_checkout.choose_from_list')}</button>
    <span class="visible-phone cm-open-pickups-msg"></span>
    {* For mobiles; button for popup with pickup points *}

    {* List wrapper *}
    <div class="ty-checkout-select-store pickup__offices-wrapper pickup__offices-wrapper-open hidden-phone">

        {* Search *}
        {if $shipping.data.stores|count >= $_max_desktop_items}
        <div class="pickup__search">
            <div class="pickup__search-field litecheckout__field">
                <input type="text" id="pickup-search" class="litecheckout__input js-pickup-search-input"
                       data-ca-pickup-group-key="{$group_key}" placeholder=" " value />
                <label class="litecheckout__label" for="pickup-search">{__("storefront_search_label")}</label>
            </div>
        </div>
        {/if}
        {* End of Search *}

        {* List *}
        <label for="pickup_office_list"
               class="cm-required cm-multiple-radios hidden"
               data-ca-validator-error-message="{__("pickup_point_not_selected")}"></label>
        <div class="litecheckout__fields-row litecheckout__fields-row--wrapped pickup__offices pickup__offices--list
             {if $shipping.data.stores|count < $_max_desktop_items}
                pickup__offices--list--no-sorting
             {/if}"
             id="pickup_office_list"
             data-ca-error-message-target-node-change-on-screen="xs,xs-large,sm"
             data-ca-error-message-target-node-after-mode="true"
             data-ca-error-message-target-node-on-screen=".cm-open-pickups-msg"
             data-ca-error-message-target-node=".pickup__offices--list"
        >
            {foreach from=$shipping.data.stores item=store}
                {include file="addons/store_locator/views/checkout/components/shippings/items/pickup.tpl" store=$store}
            {/foreach}
        </div>
        {* End of List *}

    </div>
    {* End of List wrapper *}

</div>
