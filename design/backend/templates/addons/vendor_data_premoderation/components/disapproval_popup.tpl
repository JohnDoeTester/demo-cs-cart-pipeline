{*
int    $product_id Product ID
string $title      Dialog title
*}
{$product_id = $product_id|default:0}
{$title = $title|default:__("disapprove_products")}
<div class="hidden" title="{$title}" id="disapproval_reason_{$product_id}">
    <div class="form-horizontal form-edit">
        <div class="control-group">
            <label class="control-label">
                {__("vendor_data_premoderation.disapproval_reason")}:
            </label>
            <div class="controls">
                <textarea class="input-textarea-long premoderation-reason"
                          name="product_approval[{$product_id}][reason]"
                          cols="55"
                          rows="8"
                ></textarea>
            </div>
        </div>
    </div>
    <div class="buttons-container">
        <a class="cm-dialog-closer cm-cancel tool-link btn">
            {__("cancel")}
        </a>
        <input type="submit"
               class="btn btn-primary"
               name="dispatch[premoderation.m_decline.{$product_id}]"
               value="{__("disapprove")}"
        />
    </div>
    <!--disapproval_reason_{$product_id}--></div>
