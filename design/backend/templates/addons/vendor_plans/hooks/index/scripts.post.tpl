{if $vendor_plans_payments}
<script>
Tygh.$(document).ready(function() {
    Tygh.$.get('{'vendor_plans.async?is_ajax=1'|fn_url:'A':'current' nofilter}');
});
</script>
{/if}

{script src="js/addons/vendor_plans/backend/plan_storefronts.js"}
{script src="js/addons/vendor_plans/backend/plan_usergroups.js"}
{script src="js/addons/vendor_plans/backend/plan_categories.js"}
{script src="js/addons/vendor_plans/backend/vendor_storefronts.js"}
