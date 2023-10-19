<!Doctype html>
<html>
<head>
    <title>{__("pingpp.pingpp")}</title>
    {include file="common/styles.tpl"}
</head>
<body>
<div class="pingpp-payment-container"
     data-ca-pingpp-order-id="{$order_id}"
     data-ca-pingpp-order-number="{$order_number}"
>
    <h1>{__("pingpp.paying_with", ["[channel]" => __("pingpp.channel.`$channel`")])}</h1>
    {if $instructions}
        <div class="pingpp-instructions-wrapper">
            <p>{$instructions nofilter}</p>
        </div>
    {/if}
    {if $qr_code_url}
        <div class="pingpp-qr-wrapper">
            <img src="{"qr.generate?url={$qr_code_url|escape:"url"}"|fn_url}"
                 alt="{$instructions|default:"QR"|strip_tags}"
                 class="pingpp-qr"
            />
        </div>
    {/if}
    {if $wx_pay_request}
        <script>
            var pingpp_wx_pay_request = {$wx_pay_request|json_encode nofilter};
        </script>
    {/if}
    <div class="pingpp-buttons-container ty-right">
        {include file="buttons/button.tpl"
            but_href="payment_notification.cancel?payment=pingpp&order_id=`$order_id`"
            but_text=__("cancel")
        }
    </div>
</div>
{include file="common/scripts.tpl"}
</body>
</html>
