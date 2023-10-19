{if $location == "cart" && $cart.shipping_required == true && $settings.Checkout.estimate_shipping_cost == "Y"}
    {capture name="shipping_estimation"}
        {strip}
        {include_ext file="common/icon.tpl"
            class="ty-icon-flight ty-cart-total__icon-estimation"
        }
        <a id="opener_shipping_estimation_block{$suffix_key}" {""}
           class="cm-dialog-opener cm-dialog-auto-size ty-cart-total__a-estimation" {""}
           data-ca-target-id="shipping_estimation_block{$suffix_key}" {""}
           href="{"checkout.cart"|fn_url}" {""}
           rel="nofollow"
        >
            {if $cart.shipping}
                {__("change")}
            {else}
                {__("calculate")}
            {/if}
        </a>
        {/strip}
    {/capture}
    <div class="hidden"
         id="shipping_estimation_block{$suffix_key}"
         title="{__("calculate_shipping_cost")}"
    >
        <div class="ty-cart-content__estimation">
            {include file="addons/direct_payments/views/separate_checkout/components/shipping_estimation.tpl"
                     vendor_id=$vendor_id
                     additional_id=$vendor_id
                     location="popup"
                     result_ids="shipping_estimation_link{$suffix_key}"
            }
        </div>
    </div>
{/if}
<div class="ty-cart-total">
    <div class="ty-cart-total__wrapper clearfix" id="checkout_totals{$suffix_key}">
        {if $cart_products}
            <div class="ty-coupons__container">
                {include file="addons/direct_payments/views/separate_checkout/components/promotion_coupon.tpl"}
                {hook name="checkout:payment_extra"}
                {/hook}
                </div>
        {/if}

        {hook name="checkout:payment_options"}
        {/hook}

        {include file="views/checkout/components/checkout_totals_info.tpl"}
        <div class="clearfix"></div>
        <ul class="ty-cart-statistic__total-list">
            <li class="ty-cart-statistic__item ty-cart-statistic__total">
                <span class="ty-cart-statistic__total-title">{__("total_cost")}</span>
                <span class="ty-cart-statistic__total-value">{include file="common/price.tpl" value=$_total|default:$smarty.capture._total|default:$cart.total span_id="cart_total" class="ty-price"}</span>
            </li>
        </ul>
    <!--checkout_totals{$suffix_key}--></div>

    {script src="js/addons/direct_payments/func.js"}
</div>
