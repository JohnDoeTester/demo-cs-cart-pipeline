<div class="ty-control-group ty-company__terms">
    <div class="cm-field-container">
        {strip}
            <label for="id_accept_terms_{$field.field_name}" class="{if $field.profile_required == "Y"}cm-check-agreement{/if}">
                <input type="checkbox" id="id_accept_terms_{$field.field_name}" name="{$field.field_name}" value="Y" class="{$field.class}"/>
                <a id="sw_terms_and_conditions_{$field.field_name}" class="cm-combination ty-dashed-link">
                    {$field.description}
                </a>
            </label>
        {/strip}

        <div class="hidden" id="terms_and_conditions_{$field.field_name}">
            {__("vendor_terms_n_conditions_content") nofilter}
        </div>
    </div>
    {if $field.profile_required == "Y"}
        <script>
            (function(_, $) {
                $.ceFormValidator('registerValidator', {
                    class_name: 'cm-check-agreement',
                    message: '{__("vendor_terms_n_conditions_alert")|escape:javascript}',
                    func: function(id) {
                        return $('#' + id).prop('checked');
                    }
                });
            }(Tygh, Tygh.$));
        </script>
    {/if}
</div>
