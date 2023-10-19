{*
    $item_id string                             Item identifier
    $item    \Tygh\ContextMenu\Items\ActionItem Action item
    $data    array                              Data from context_menu schema
    $params  array                              Сontext menu component parameters
*}

<li class="btn bulk-edit__btn bulk-edit__btn--group dropleft-mod">
            <span class="bulk-edit__btn-content bulk-edit-toggle bulk-edit__btn-content--group" data-toggle=".bulk-edit__content--group">
                {__("group")}
                <span class="caret mobile-hide"></span>
            </span>

    <div class="bulk-edit--reset-dropdown-menu bulk-edit__content bulk-edit__content--group">
        <div class="bulk-edit-inner bulk-edit-inner--group">
            <div class="bulk-edit-inner__header">
                <span>{__("group")}</span>
            </div>
            <div class="bulk-edit-inner__body">
                <select name="feature_data[parent_id]"
                        id="elm_feature_group_{$id}"
                        data-ca-feature-id="{$id}"
                        data-ca-bulkedit-group-changer
                        class="cm-feature-group"
                >
                    <option value="0">-{__("none")}-</option>
                    {foreach $group_features as $group_feature}
                        <option data-ca-display-on-product="{$group_feature.display_on_product}" data-ca-display-on-catalog="{$group_feature.display_on_catalog}" data-ca-display-on-header="{$group_feature.display_on_header}" value="{$group_feature.feature_id}"{if $group_feature.feature_id == $feature.parent_id}selected="selected"{/if}>{$group_feature.description}</option>
                    {/foreach}
                </select>
            </div>
            <div class="bulk-edit-inner__footer">
                <button class="btn bulk-edit-inner__btn bulkedit-group-cancel"
                        role="button"
                        data-ca-bulkedit-group-cancel
                        data-ca-bulkedit-group-reset-changer="[data-ca-bulkedit-group-changer]"
                >{__("reset")}</button>
                <button class="btn btn-primary bulk-edit-inner__btn bulkedit-group-update"
                        role="button"
                        data-ca-bulkedit-group-update
                        data-ca-bulkedit-group-values="[data-ca-bulkedit-group-changer]"
                        data-ca-bulkedit-group-target-form="[name=manage_product_features_form]"
                        data-ca-bulkedit-group-target-form-active-objects="tr.selected:has(input[type=checkbox].cm-item:checked)"
                        data-ca-bulkedit-group-dispatch="product_features.m_set_group"
                >{__("apply")}</button>
            </div>
        </div>
    </div>

    <div class="bulk-edit--overlay"></div>
</li>