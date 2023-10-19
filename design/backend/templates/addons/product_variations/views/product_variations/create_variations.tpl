<div class="tabs cm-j-tabs">
    <ul class="nav nav-tabs">
        {if $is_allow_generate_variations}
            <li id="tab_create_new_{$product_data.product_id}" class="cm-js active">
                <a>{__("product_variations.create_new")}</a>
            </li>
        {/if}
        <li id="tab_link_existing_{$product_data.product_id}" class="cm-js cm-ajax {if !$is_allow_generate_variations}active{/if}">
            <a href="{"product_variations.find_variations?product_id={$product_data.product_id}"|fn_url}">{__("product_variations.link_existing")}</a>
        </li>
    </ul>
</div>
<div class="cm-tabs-content" id="tabs_content_{$product_data.product_id}">
    {if $is_allow_generate_variations}
        <div id="content_tab_create_new_{$product_data.product_id}">
            {include file="addons/product_variations/views/product_variations/components/generate_variations.tpl"}
        </div>
    {/if}
    <div id="content_tab_link_existing_{$product_data.product_id}">&nbsp;
    <!--content_tab_link_existing_{$product_data.product_id}--></div>
</div>
<div class="buttons-container product-variations__add-variations-buttons-container">
    <div>
        <a class="cm-dialog-closer cm-cancel tool-link btn">{__("cancel")}</a>
    </div>
    {if $is_allow_generate_variations}
        <div class="cm-tab-tools tab-tools" id="tools_tab_create_new_{$product_data.product_id}">
            {if $new_combinations_count}
                {include file="buttons/button.tpl" but_text=__("product_variations.generator.create_btn", [$new_combinations_count]) but_role="submit-link" but_name="dispatch[product_variations.generate]" but_meta="btn-primary" but_target_form="generate_product_to_group_form"}
            {/if}
        <!--tools_tab_create_new_{$product_data.product_id}--></div>
    {/if}
    <div class="cm-tab-tools tab-tools" id="tools_tab_link_existing_{$product_data.product_id}">
    <!--tools_tab_link_existing_{$product_data.product_id}--></div>
</div>
