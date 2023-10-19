{*
    $item_id string                                Item identifier
    $item    \Tygh\ContextMenu\Items\ComponentItem Data from context_menu schema
    $data    array                                 Data from context_menu schema
    $params  array                                 Сontext menu component parameters
*}

{capture assign="content"}
    <div class="bulk-edit-inner__header">
        <span>{__($data.name.template, $data.name.params)}</span>
    </div>

    <div class="bulk-edit-inner__body">
        <div class="bulk-edit-inner__input-group">
            <input type="number"
                   step="any"
                   class="input-group__text"
                   placeholder="{__("amount")}"
                   data-ca-bulkedit-mod-changer
                   data-ca-bulkedit-mod-affect-on="[data-ca-bulkedit-mod-amount]"
                   data-ca-bulkedit-mod-filter="[data-ca-bulkedit-mod-amount-filter-a]"
            />
            <select class="input-group__modifier" data-ca-bulkedit-mod-amount-filter-a>
                <option value="number">{$currencies.$primary_currency.symbol nofilter}</option>
                <option value="percent">%</option>
            </select>
        </div>

        <div class="bulk-edit-inner__hint">
            <span>{__("bulk_edit.decrease_hint")}</span>
        </div>

        <div class="bulk-edit-inner__example">
            <p class="bulk-edit-inner__example-title">{__("bulk_edit.example_of_modified_value")}</p>

                <p class="bulk-edit-inner__example-line">
                    <span class="bulk-edit-inner__example-line--left">{__("current_amount")}:</span>
                    <span class="bulk-edit-inner__example-line--right"
                          data-ca-bulkedit-mod-default-value="30.00"
                          data-ca-bulkedit-mod-affected-write-into=".bulk-edit-inner__example-line--red"
                          data-ca-bulkedit-mod-affected-old-value=".bulk-edit-inner__example-line--green"
                          data-ca-bulkedit-mod-amount
                    >
                        <span class="bulk-edit-inner__example-line--green">30.00</span>
                        <span class="bulk-edit-inner__example-line--red"></span>
                    </span>
                </p>
        </div>
    </div>

    <div class="bulk-edit-inner__footer">
        <button class="btn bulk-edit-inner__btn bulkedit-mod-cancel"
                role="button"
                data-ca-bulkedit-mod-cancel
                data-ca-bulkedit-mod-reset-changer="[data-ca-bulkedit-mod-changer]"
        >{__("reset")}</button>
        <button class="btn btn-primary bulk-edit-inner__btn bulkedit-mod-update"
                role="button"
                data-ca-bulkedit-mod-update
                data-ca-bulkedit-mod-values="[data-ca-bulkedit-mod-changer]"
                data-ca-bulkedit-mod-target-form="[name={$params.form}]"
                data-ca-bulkedit-mod-target-form-active-objects="tr.selected:has(input[type=checkbox].cm-item:checked)"
                data-ca-bulkedit-mod-dispatch="gift_certificates.m_update_amount"
        >{__("apply")}</button>
    </div>
{/capture}

{include file="components/context_menu/items/dropdown.tpl"
    content=$content
    data=$data
    id=$item_id
}
