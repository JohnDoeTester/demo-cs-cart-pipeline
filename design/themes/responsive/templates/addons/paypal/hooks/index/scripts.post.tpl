{script src="js/addons/paypal/in_context_checkout.js" cookie-name="paypal"}

<script>
    (function (_, $) {
        _.tr({
            "paypal.paypal_cookie_title": '{__("paypal.paypal_cookie_title", ['skip_live_editor' => true])|escape:"javascript"}',
            "paypal.paypal_cookie_description": '{__("paypal.paypal_cookie_description", ['skip_live_editor' => true])|escape:"javascript"}',
        });
    })(Tygh, Tygh.$);
</script>