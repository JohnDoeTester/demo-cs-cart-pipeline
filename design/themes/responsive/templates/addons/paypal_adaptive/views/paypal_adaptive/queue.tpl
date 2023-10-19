{hook name="paypal_adaptive:queue"}
{/hook}

{if $order_info.payment_method.processor_params.in_context == 'Y'}
    {assign var="pop_up" value="true"}
{/if}
{foreach from=$queue_orders item=queue key=index}
    {assign var="pay" value=($pay_step==$index+1)}
    <form name="payments_form_{$index}" action="{""|fn_url}" method="post" class="payments-form">

        <input type="hidden" id="order_ids" name="order_ids" value="{','|implode:$queue.order_ids}">

        <div class="ty-step__container{if $pay}-active{/if}">

            <h3 class="ty-step__title{if $pay}-active{/if}{if $queue.paid && !$pay}-complete{/if} clearfix">
                <span class="ty-step__title-left">{if $queue.paid}{include_ext file="common/icon.tpl" class="ty-icon-ok ty-step__title-icon"}{else}{$index+1}{/if}</span>
                {include_ext file="common/icon.tpl" class="ty-icon-down-micro ty-step__title-arrow"}

                {if $queue.paid}
                    <span class="ty-step__title-txt">{__("paypal_adaptive_paid")} {include file="common/price.tpl" value=$_total|default:$queue.total}</span>
                {else}
                    <span class="ty-step__title-txt">{__("paypal_adaptive_pay")} {include file="common/price.tpl" value=$_total|default:$queue.total}</span>
                {/if}
            </h3>

            <div id="step_{$index+1}_body" class="ty-step__body{if $pay}-active{/if} {if !$pay}hidden{/if} clearfix">

                <div class="clearfix">
                    <div class="checkout__block">
                        {include file="addons/paypal_adaptive/views/paypal_adaptive/items.tpl" queue=$queue}
                    </div>
                </div>

                <div class="ty-checkout-buttons">
                    {include file="buttons/button.tpl" but_href=$script_proceed but_text=__("paypal_adaptive_pay") but_role="text" but_id="place_order_`$index`" but_target_form="payments_form_`$index`" but_meta="ty-btn__secondary"}
                    {if !$exist_paid}
                        &nbsp;{include file="buttons/button.tpl" but_href=$script_cancel but_text=__("paypal_adaptive_cancel") but_role="text"}
                    {/if}
                </div>
            </div>
        </div>
    </form>
{/foreach}

{capture name="mainbox_title"}<span class="ty-checkout__title">{__("paypal_adaptive_progress_payment_order")}&nbsp;{include_ext file="common/icon.tpl" class="ty-icon-lock ty-checkout__title-icon"}</span>{/capture}

{if $pop_up}

    {if $order_info.payment_method.processor_params.mode == 'test'}
        {assign var="flow_url" value="https://www.sandbox.paypal.com/webapps/adaptivepayment/flow/pay?paykey="}
    {else}
        {assign var="flow_url" value="https://www.paypal.com/webapps/adaptivepayment/flow/pay?paykey="}
    {/if}

    {assign var="check_url" value="paypal_adaptive.get_url?payKey="|fn_url}
    {assign var="paykey_url" value="paypal_adaptive.get_paykey"|fn_url}

    <script {$script_attrs|render_tag_attrs nofilter}>

        var isSafari = function(){
            var ua = navigator.userAgent.toLowerCase(),
                result = false;
            if (ua.indexOf('safari') != -1) {
                if (!(ua.indexOf('chrome') > -1)) {
                    result = true; // Safari
                }
            }
            return result;
        };

        if (!isSafari()) {

            $.getScript('https://www.paypalobjects.com/js/external/apdg.js');

            var paykey_url = "{$paykey_url|escape:javascript nofilter}",
                    check_url = "{$check_url|escape:javascript nofilter}",
                    flow_url = "{$flow_url|escape:javascript nofilter}",
                    payment_id = "{$order_info.payment_method.payment_id|escape:javascript nofilter}",
                    button_id = "place_order",
                    form_name = "payments_form",
                    dgFlowMini,
                    payKey,
                    suffix = '{$active|escape:javascript nofilter}',
                    script_proceed = '{$script_proceed|escape:javascript nofilter}';

            var adaptiveIsSelected = function () {
                var chBox = $('input[name=payment_id]:checked');
                return chBox.attr('value') == payment_id || chBox.length == 0;
            };

            var returnFromPaypal = function () {
                $.ceAjax("request", check_url + payKey + '&payment_id=' + payment_id, {
                    method: "get",
                    callback: function (response) {
                        if (response.url) {
                            {literal}
                            window.location.href = response.url.replace("${payKey}", payKey) + '&payment_id=' + payment_id;
                            {/literal}
                        }
                    }
                });
            };

            var isIOS = function () {
                return /(iPad|iPhone|iPod)/.test(navigator.userAgent);
            };

            $('form[name^=' + form_name + ']').submit(function (e) {
                e.preventDefault();
            });

            var pay = function (e) {

            };

            $('a[id^=' + button_id + ']').on('click', function (e) {
                e.preventDefault();
                var form_name = $(this).data('caTargetForm'),
                        order_ids = $('form[name=' + form_name + ']').children('#order_ids').val(),
                        params = {
                            payment_id: payment_id,
                            order_ids: order_ids
                        };

                $.ceAjax('request', paykey_url, {
                    method: 'get',
                    data: params,
                    callback: function (data) {
                        payKey = data.payKey;
                        checkout(payKey, form_name);
                    }
                });

                var checkout = function (payKey, form_name) {
                    e.preventDefault();

                    var dataToSend = $('form[name=' + form_name + ']').serialize() + '&is_ajax=1' + '&adaptive=true';

                    if (!adaptiveIsSelected()) {
                        return;
                    }

                    dgFlowMini = new PAYPAL.apps.DGFlowMini({
                        callbackFunction: 'returnFromPaypal'
                    });

                    $.ajax({
                        type: 'GET',
                        url: "index.php",
                        async: !isIOS(),
                        data: dataToSend,
                        success: function (data) {

                            if (payKey === undefined) {
                                location.reload();
                            }

                            if (typeof PAYPAL !== "undefined" && typeof payKey !== "undefined") {
                                dgFlowMini.startFlow(flow_url + payKey + "&expType=mini");
                            }
                        },
                        error: function () {
                            location.reload();
                        }
                    });
                };
            });
        }
    </script>
{/if}
