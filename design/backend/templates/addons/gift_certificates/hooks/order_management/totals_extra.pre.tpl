<div class="control-group">
    <div class="controls">
        <select name="gift_cert_code" id="gift_cert_code">
            <option value="" disabled selected hidden>{__("gift_cert_code")}</option>
            <option value=""> -- </option>
            {foreach from=$gift_certificates item="code"}
                <option value="{$code}">{$code}</option>
            {/foreach}
        </select>
    </div>
</div>