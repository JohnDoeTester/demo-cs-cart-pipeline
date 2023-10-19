{if "MULTIVENDOR"|fn_allowed_for && $auth.user_type === "UserTypes::VENDOR"|enum}
    {$allow_create_product = $addons.master_products.allow_vendors_to_create_products === "YesNo::YES"|enum scope=parent}
    
    <li>{btn type="list" text=__("master_products.add_product_from_catalog") href="products.master_products"}</li>
{/if}