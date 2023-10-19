{*
    $select_storefront              bool                                Select storefront
*}

{*
    To hide the storefront picker in ULTIMATE
*}
{if "ULTIMATE"|fn_allowed_for
    && $runtime.controller === 'reviews'
    && (
        $runtime.mode === 'manage'
        && $addons.product_reviews.split_reviews_by_storefronts !== "YesNo::YES"|enum
        || $runtime.mode === 'update'
    )
}
    {$select_storefront = false scope=parent}
{/if}
