{foreach $stripe_payment_buttons_icons as $payment_type}
    <span class="ty-payment-icons__item stripe-payment-icon stripe-payment-icon--{$payment_type}">
        {include file="common/image.tpl" images = ["image_path" => "`$images_dir`/addons/stripe/payments/`$payment_type`_mark.png", "image_x" => 51, "image_y" => 32]}
    </span>
{/foreach}
