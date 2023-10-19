{$suffix_key="_`$vendor_id`"}
{$result_ids="cart_items`$suffix_key`,checkout_totals`$suffix_key`,checkout_steps`$suffix_key`,cart_status*,checkout_totals_header* ,checkout_cart`$suffix_key`"}

<div class="clearfix">
    <form name="cart_form{$suffix_key}"
          id="cart_form{$suffix_key}"
          class="cm-check-changes cm-ajax cm-ajax-full-render"
          action="{""|fn_url}"
          method="post"
          enctype="multipart/form-data"
    >
        <input type="hidden" name="redirect_mode" value="cart"/>
        <input type="hidden" name="vendor_id" value="{$vendor_id}"/>
        <input type="hidden" name="result_ids" value="{$result_ids}"/>
        <input type="submit" class="ty-btn--recalculate-cart hidden" name="dispatch[checkout.update]" />

        {if $vendor}
            <div class="ty-float-right hidden ty-cart__title-total"
                 data-ca-switch-id="cart{$suffix_key}"
                 id="checkout_totals_header{$suffix_key}">
                {__("total")}:
                {include file="common/price.tpl" value=$cart.total}
            <!--checkout_totals_header{$suffix_key}--></div>
            <h2 class="ty-hand cm-combinations ty-cart-vendor__title"
                id="sw_cart{$suffix_key}"
            >
                {$vendor.company}
                {include_ext file="common/icon.tpl"
                    class="ty-icon-down-micro ty-sort-dropdown__icon hidden"
                    data=[
                        "data-ca-switch-id" => "cart`$suffix_key`"
                    ]
                }
                {include_ext file="common/icon.tpl"
                    class="ty-icon-up-micro ty-sort-dropdown__icon"
                    data=[
                        "data-ca-switch-id" => "cart`$suffix_key`"
                    ]
                }
            </h2>
        {/if}

        <div data-ca-switch-id="cart{$suffix_key}">

            {include file="addons/direct_payments/views/separate_checkout/components/cart_items.tpl"
                     disable_ids="button_cart"
            }

        </div>
    </form>

    <div data-ca-switch-id="cart{$suffix_key}">

        {include file="addons/direct_payments/views/separate_checkout/components/checkout_totals.tpl"
                 location="cart"
        }

        <div class="buttons-container ty-cart-content__bottom-buttons clearfix">
            <div class="ty-float-left ty-cart-content__left-buttons">
                {include file="buttons/continue_shopping.tpl"
                         but_href=$continue_url|fn_url
                }
                {include file="buttons/clear_cart.tpl"
                         but_href="checkout.clear&vendor_id=`$vendor_id`"
                         but_role="text"
                         but_meta="cm-confirm ty-cart-content__clear-button"
                }
            </div>

            <div class="ty-float-right ty-cart-content__right-buttons">
                {if $payment_methods}
                    {$m_name="checkout"}
                    {$link_href="checkout.checkout&vendor_id=`$vendor_id`"}
                    {include file="buttons/proceed_to_checkout.tpl"
                             but_href=$link_href
                    }
                {/if}
            </div>
            {if !$payment_methods}
                <div class="clearfix ty-cart-content__payments-warning">
                    <p class="ty-float-right ty-cart-content__payments-warning__text"
                    >{__("cannot_proccess_checkout_without_payment_methods")}</p>
                </div>
            {/if}
        </div>
    </div>
</div>
