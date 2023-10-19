<div id="content_blocks">
    {if $layouts|count > 1}
        <div class="content-variant-wrap content-variant-wrap--layout">
            <h6 class="muted">{__("switch_layout")}:</h6>
            {include file="common/select_object.tpl"
                style="graphic"
                link_tpl=$config.current_url|fn_link_attach:"s_layout="
                items=$layouts
                selected_id=$runtime.layout.layout_id
                key_name="name"
                display_icons=false
                target_id="content_blocks"
            }
        </div>
    {/if}
    {include file="views/block_manager/manage.tpl"
        storefront_selector_submit_form_class="cm-ajax"
        storefront_selector_submit_form_result_ids="content_blocks"
        storefront_selector_submit_form_params=$dynamic_object_params
    }
<!--content_blocks--></div>
