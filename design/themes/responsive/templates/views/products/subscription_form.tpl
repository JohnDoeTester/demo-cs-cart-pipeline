{$obj_id=$obj_id|default:$product_id}
<div id="subscribe_form_wrapper">
    {hook name="product_data:back_in_stock_checkbox"}
        <div class="ty-control-group">
            <label for="sw_product_notify_{$obj_prefix}{$obj_id}" class="ty-strong" id="label_sw_product_notify_{$obj_prefix}{$obj_id}">
                <input id="sw_product_notify_{$obj_prefix}{$obj_id}"
                   data-ca-product-notify-stock=""
                   data-ca-product-object-prefix="{$obj_prefix}"
                   data-ca-product-id="{$obj_id}"
                   data-ca-auth-user-id="{$auth.user_id}"
                   type="checkbox"
                   class="checkbox cm-switch-availability cm-switch-visibility"
                   name="product_notify"
                   {if $product_notification_enabled === "YesNo::YES"|enum}checked="checked"{/if}/>{__("notify_when_back_in_stock")}
            </label>
        </div>
    {/hook}
    {if !$auth.user_id }
        <div class="{if $product_notification_enabled !== "YesNo::YES"|enum}hidden{/if}" id="product_notify_{$obj_prefix}{$obj_id}">
            {hook name="product_data:back_in_stock_email"}
                <div class="ty-control-group ty-input-append ty-product-notify-email">

                    <input type="hidden" name="enable" value="Y" disabled />
                    <input type="hidden" name="product_id" value="{$product_id}" disabled />

                    <label id="product_notify_email_label" for="product_notify_email_{$obj_prefix}{$obj_id}" class="cm-required cm-email hidden">{__("email")}</label>
                    <input type="text" name="email" id="product_notify_email_{$obj_prefix}{$obj_id}" size="20" value="{$product_notification_email}" placeholder="{__("enter_email")}" class="ty-product-notify-email__input cm-hint" title="{__("enter_email")}" disabled />

                    <button class="ty-btn-go cm-ajax" type="submit" name="dispatch[products.product_notifications]" title="{__("go")}">{include_ext file="common/icon.tpl" class="ty-icon-right-dir ty-btn-go__icon"}</button>

                </div>
            {/hook}
        </div>
    {/if}
<!--subscribe_form_wrapper--></div>
