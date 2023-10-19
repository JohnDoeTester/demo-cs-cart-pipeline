<div id="age_verification_fields" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label for="age_verification" class="control-label">{__("age_verification")}:</label>
            <div class="controls">
                <input type="hidden" name="{$array_name}[age_verification]" value="N">
                <input type="checkbox" id="age_verification" name="{$array_name}[age_verification]" value="Y" {if $record.age_verification == "Y"}checked="checked"{/if}>
            </div>
        </div>

        <div class="control-group">
            <label for="age_limit" class="control-label">{__("age_limit")}:</label>
            <div class="controls">
                <input type="text" id="age_limit" name="{$array_name}[age_limit]" size="10" maxlength="2" value="{$record.age_limit|default:"0"}" class="input-micro">
                <span> &nbsp; {__("years")}</span>
            </div>
        </div>

        <div class="control-group">
            <label for="age_warning_message" class="control-label">{__("age_warning_message")}:</label>
            <div class="controls">
                <div class="input-group {$input_append}">
                    <textarea id="age_warning_message"
                        class="input-large"
                        name="{$array_name}[age_warning_message]"
                        cols="55"
                        rows="4"
                    >{$record.age_warning_message}</textarea>
                    {include file="components/append_language.tpl"
                        hide_inputs=$hide_inputs
                    }
                </div>
            </div>
        </div>
    </fieldset>
</div>