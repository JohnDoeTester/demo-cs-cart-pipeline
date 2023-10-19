
{if "MULTIVENDOR"|fn_allowed_for}
<div id="content_terms_and_conditions" class="hidden">
    <div class="control-group">
        <label class="control-label" for="elm_company_terms">{__("vendor_terms.terms_and_conditions")}:</label>
        <div class="controls">
            <div class="input-group {$input_append_wysiwyg}">
                <textarea id="elm_company_terms"
                    name="company_data[terms]"
                    cols="55"
                    rows="8"
                    class="cm-wysiwyg input-large"
                >{$company_data.terms}</textarea>
                {include file="components/append_language.tpl"
                    hide_inputs=$hide_inputs
                    simple_tooltip=true
                }
            </div>
            <p class="muted description">{__("vendor_terms.terms_and_conditions_tooltip")}</p>
        </div>
    </div>
</div>
{/if}
