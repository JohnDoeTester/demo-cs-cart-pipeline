{if $smarty.session.stripe_connect_order_id}
    <script>
        (function(_, $) {
            $.ceAjax('request', fn_url('stripe_connect.update_payments_description?order_id=' + {$smarty.session.stripe_connect_order_id}), {
                method: 'post',
                hidden: true,
                caching: false
            });
        }(Tygh, Tygh.$));
    </script>
{/if}
