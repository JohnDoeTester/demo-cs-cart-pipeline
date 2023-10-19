{hook name="paypal_adaptive:payments_paypal_adaptive"}
{/hook}

{if $cart.product_groups|@count > $payment_method.processor_params.max_amount_vendors_in_order}
    {assign var="pop_up" value="true"}
{/if}
{if $payment_method.processor_params.in_context == 'Y' && !$pop_up}

    {if $payment_info.processor_params.mode == 'test'}
        {assign var="flow_url" value="https://www.sandbox.paypal.com/webapps/adaptivepayment/flow/pay?paykey="}
    {else}
        {assign var="flow_url" value="https://www.paypal.com/webapps/adaptivepayment/flow/pay?paykey="}
    {/if}

    {script  src="https://www.paypalobjects.com/js/external/apdg.js"}
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
            var paykey_url = "{$paykey_url|escape:javascript nofilter}",
                check_url = "{$check_url|escape:javascript nofilter}",
                flow_url = "{$flow_url|escape:javascript nofilter}",
                tab_id = "{$tab_id|escape:javascript nofilter}",
                payment_id = "{$payment_id|escape:javascript nofilter}",
                button_id = "place_order_" + tab_id,
                form_name = "payments_form_" + tab_id,
                dgFlowMini,
                payKey,
                suffix = '{$active|escape:javascript nofilter}';

            {literal}

            var checkAgreements = function (suffix) {
                var checked = true;

                $('form[name=payments_form_' + suffix + '] input[type=checkbox].cm-agreement').each(function(index, value) {
                    if($(value).prop('checked') === false) {
                        checked = false;
                    }
                });

                return checked;
            };

            var adaptiveIsSelected = function () {
                var chBox = $('input[name=payment_id]:checked');
                return chBox.attr('value') == payment_id || chBox.length == 0;
            };

            var returnFromPaypal = function () {
                $.ceAjax("request", check_url + payKey + '&payment_id=' + payment_id, {
                    method: "get",
                    callback: function (response) {
                        if (response.url) {
                            window.location.href = response.url.replace("${payKey}", payKey) + '&payment_id=' + payment_id;
                        }
                    }
                });
            };

            var isIOS = function () {
                return /(iPad|iPhone|iPod)/.test(navigator.userAgent);
            };

            $('form[name=' + form_name + ']').submit(function (e) {
                e.preventDefault();
            });

            document.getElementById(button_id).onclick  = function (e) {
                if (!adaptiveIsSelected()) return;

                dgFlowMini = new PAYPAL.apps.DGFlowMini({
                    callbackFunction: 'returnFromPaypal'
                });

                var dataToSend = $('form[name=' + form_name + ']').serialize() + '&is_ajax=1'+'&adaptive=true';

                if (checkAgreements(suffix)) {
                    $.ajax({
                        type: 'POST',
                        url: "index.php",
                        async:   !isIOS(),
                        data: dataToSend,
                        success: function (data) {
                            payKey = data.paykey;

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
                }
            };
            {/literal}
        }
    </script>
{/if}
