<div id="content_attachments" class="cm-hide-save-button {if $selected_section !== "attachments"}hidden{/if}">
    {if !("ULTIMATE"|fn_allowed_for && ($runtime.company_id && $product_data.shared_product === "YesNo::YES"|enum && $product_data.company_id != $runtime.company_id))}
        {include file="addons/attachments/views/components/add_new_attachments_files.tpl"
            attachment=[]
            object_id=$smarty.request.product_id
            object_type="product"
        }
    {/if}

    {component name="configurable_page.field" entity="products" tab="attachments" section="main" field="attachments"}
        {include file="addons/attachments/views/attachments/manage.tpl"
            object_id=$smarty.request.product_id
            object_type="product"
            hide_add_new_attachments=true
        }
    {/component}
<!--content_attachments--></div>