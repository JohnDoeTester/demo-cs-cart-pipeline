<script>
    (function(_, $) {
        $.ceEvent('on', 'ce.commoninit', function () {
            var $isFulfillmentByMarketplace = '{$is_fulfillment_by_marketplace}';
            if ($isFulfillmentByMarketplace) {
                $('.shipping_methods').prop('disabled', false);
            } else {
                $('.shipping_methods').prop('disabled', true);
            }
        });
    })(Tygh, Tygh.$);
</script>