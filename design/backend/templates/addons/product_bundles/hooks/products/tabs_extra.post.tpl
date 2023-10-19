{$hide_controls = !$product_data.company_id}

<div id="content_product_bundles" class="cm-hide-save-button {if $selected_section !== "product_bundles"}hidden{/if}">
    {if !$hide_controls && $is_allowed_to_create_product_bundles}
    <div class="clearfix">
        <div class="pull-right">
            {capture name="add_new_picker"}
                <div id="add_new_bundle">
                    {include file="addons/product_bundles/views/product_bundles/update.tpl"
                        product_id=$product_data.product_id
                        item=["company_id" => $product_data.company_id]
                    }
                </div>
            {/capture}
            {include file="common/popupbox.tpl"
                id="add_new_bundle"
                text=__("product_bundles.add_new_bundle")
                content=$smarty.capture.add_new_picker
                link_text=__("product_bundles.add_new_bundle")
                title=__("product_bundles.add_new_bundle")
                act="general"
                icon="icon-plus"
            }
        </div>
    </div><br>
    {/if}

    <form action="{""|fn_url}" method="post" name="manage_product_bundle_form" class="form-horizontal form-edit cm-ajax" id="manage_product_bundles_form">
        <input type="hidden" name="redirect_url" value="{$config.current_url|fn_link_attach:"selected_section=product_bundles"}" />
        <div id="update_bundles_list">
        {if $bundles}
            {$context_menu_id = "context_menu_{uniqid()}"}
            {capture name="product_bundles_table"}
                <div class="items-container">
                    <div class="table-responsive-wrapper longtap-selection">
                        <table class="table table-middle table--relative table-objects table-responsive">
                            <thead
                                    data-ca-bulkedit-default-object="true"
                                    data-ca-bulkedit-component="defaultObject"
                            >
                            <tr>
                                <th class="left" width="6%">
                                    {include file="common/check_items.tpl"
                                        elms_container="#`$context_menu_id`"
                                    }

                                    <input type="checkbox"
                                           class="bulkedit-toggler hide"
                                           data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                                           data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                                    />
                                </th>
                                <th width="1%"></th>
                                <th width="28%"></th>
                                <th width="50%"></th>
                                <th width="10%"></th>
                                <th width="12%"></th>
                            </tr>
                            </thead>
                            {foreach $bundles as $bundle}
                                {$link_text=__("edit")}
                                {$return_url="`$config.current_url`&selected_section=product_bundles"|escape:"url"}
                                
                                {include file="common/object_group.tpl"
                                    id=$bundle.bundle_id
                                    id_prefix="_product_bundle_"
                                    text=$bundle.name
                                    status=$bundle.status
                                    hidden=false
                                    href="product_bundles.update?bundle_id=`$bundle.bundle_id`&return_url=`$return_url`"
                                    link_text=$link_text
                                    object_id_name="bundle_id"
                                    table="product_bundles"
                                    href_delete="product_bundles.delete?bundle_id=`$bundle.bundle_id`&return_url=`$return_url`"
                                    delete_target_id="update_bundles_list"
                                    header_text=$bundle.name
                                    skip_delete=false
                                    no_table=true
                                    hide_for_vendor=false
                                    is_bulkedit_menu=true
                                    checkbox_col_width="1%"
                                    checkbox_name="bundle_ids[]"
                                    show_checkboxes=true
                                    hidden_checkbox=true
                                    checkbox_col_width="6%"
                                }
                            {/foreach}
                        </table>
                    </div>
                </div>
            {/capture}

            {include file="common/context_menu_wrapper.tpl"
                id=$context_menu_id
                form="manage_product_bundles_form"
                object="product_bundles"
                items=$smarty.capture.product_bundles_table
            }
        {else}
            {hook name="products:product_bundles_info"}
                <p class="no-items">{__("no_data")}</p>
            {/hook}
        {/if}
        <!--update_bundles_list--></div>
    </form>
    <!--content_product_bundles--></div>