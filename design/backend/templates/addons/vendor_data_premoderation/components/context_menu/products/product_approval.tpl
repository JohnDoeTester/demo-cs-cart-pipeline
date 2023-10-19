{hook name="products:bulk_edit_items_product_approval"}
    <li class="btn bulk-edit__btn bulk-edit__btn--actions dropleft-mod">
        <span class="bulk-edit__btn-content dropdown-toggle"
              data-toggle="dropdown"
        >
            {__("product_approval")}
            <span class="caret mobile-hide"></span>
        </span>

        <ul class="dropdown-menu">
            <li>
                {btn type="list"
                    text=__("approve_selected")
                    dispatch="dispatch[premoderation.m_approve]"
                    form="manage_products_form"
                }
            </li>

            <li>
                <a data-ca-target-id="disapproval_reason_0"
                   class="cm-dialog-opener cm-dialog-auto-size"
                >
                    {__("disapprove_selected")}
                </a>
            </li>
        </ul>
    </li>

    {include file = "addons/vendor_data_premoderation/components/disapproval_popup.tpl"
        product_id = 0
        title = __("vendor_data_premoderation.disapprove_products")
    }
{/hook}
