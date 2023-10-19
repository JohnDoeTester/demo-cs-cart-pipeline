{if $view_uri && $runtime.company_id && "ULTIMATE"|fn_allowed_for || "MULTIVENDOR"|fn_allowed_for}
    {component name="configurable_page.field" entity="products" tab="seo" section="main" field="seo_name_field"}
        {include file="addons/seo/common/seo_name_field.tpl" object_data=$product_data object_name="product_data" object_id=$product_data.product_id object_type="p" share_dont_hide=true}
    {/component}
{/if}
