{*
    $item_id string                             Item identifier
    $item    \Tygh\ContextMenu\Items\ActionItem Action item
    $data    array                              Data from context_menu schema
    $params  array                              Сontext menu component parameters
*}

<li class="btn bulk-edit__btn bulk-edit__btn--category dropleft-mod">
            <span class="bulk-edit__btn-content bulk-edit-toggle bulk-edit__btn-content--category" data-toggle=".bulk-edit__content--categories">
                {__("category")}
                <span class="caret mobile-hide"></span>
            </span>

    <div class="bulk-edit--reset-dropdown-menu bulk-edit__content bulk-edit__content--categories">
        <div class="bulk-edit-inner bulk-edit-inner--categories">
            <div class="bulk-edit-inner__header">
                <span>{__("categories")}</span>
            </div>

            <div class="bulk-edit-inner__body" id="bulk_edit_categories_list">

                <div class="bulk-edit-inner__hint">
                    <div class="bulk-edit-inner__hint--warning alert alert-warning hidden">
                <span>
                    {__("bulk_product_features_warning")}
                    <a href="{"product_features.groups"|fn_url}" target="_blank">{__("view_feature_groups")}</a>
                </span>
                    </div>
                    <p><strong>{__("bulk_edit.what_do_these_checkboxes_mean")} (<a href="#" class="cm-toggle" data-toggle=".bulk-edit-inner--categories .bulk-edit-inner__hint > .bulk-edit--category-hint-wrapper" data-show-text="{__("show")}" data-hide-text="{__("hide")}" data-state="show">{__("show")}</a>)</strong></p>

                    <div class="bulk-edit--category-hint-wrapper hidden">
                        <span><input type="checkbox" class="cm-readonly no-margin" checked="checked" /> {__("bulk_edit.what_do_these_checkboxes_mean_checked")}</span> <br />
                        <span><input type="checkbox" class="cm-readonly no-margin" /> {__("bulk_edit.what_do_these_checkboxes_mean_unchecked")}</span> <br />
                        <span><input type="checkbox" class="cm-readonly no-margin" data-set-indeterminate="true" /> {__("bulk_edit.what_do_these_checkboxes_mean_indeterminate")}</span>

                        <hr>
                    </div>
                </div>

                <div class="control-group">
                    <div class="controls" id="bulk_edit_categories_list_content">
                        {include file="common/select2/categories_bulkedit.tpl"
                            select2_multiple=true
                            select2_select_id="product_features_categories_add_{$rnd|default:uniqid()}"
                            select2_name="product_features[category_ids]"
                            select2_allow_sorting=true
                            select2_dropdown_parent="#bulk_edit_categories_list_content"
                            select2_category_ids=$bulk_edit_ids_flat
                            select2_bulk_edit_mode=true
                            select2_bulk_edit_mode_category_ids=$bulk_edit_ids
                            disable_categories=true
                            select2_wrapper_meta="cm-field-container"
                            select2_select_meta="input-large bulk-edit__categories-picker"
                        }
                        <!--bulk_edit_categories_list_content--></div>
                </div>
                <!--bulk_edit_categories_list--></div>

            <div class="bulk-edit-inner__footer">
                <button class="btn bulk-edit-inner__btn"
                        role="button"
                        data-ca-bulkedit-mod-cat-cancel
                >{__("reset")}</button>
                <button class="btn btn-primary bulk-edit-inner__btn"
                        role="button"
                        data-ca-bulkedit-mod-result-ids="pagination_contents"
                        data-ca-bulkedit-mod-object-type="features"
                        data-ca-bulkedit-mod-cat-update
                        data-ca-bulkedit-mod-can-all-categories-be-deleted="true"
                        data-ca-bulkedit-mod-target-form="[name=manage_product_features_form]"
                        data-ca-bulkedit-mod-target-form-active-objects="tr.selected:has(input[type=checkbox].cm-item:checked)"
                        data-ca-bulkedit-mod-dispatch="product_features.m_set_categories"
                >{__("apply")}</button>
            </div>
        </div>
    </div>

    <div class="bulk-edit--overlay"></div>
</li>
