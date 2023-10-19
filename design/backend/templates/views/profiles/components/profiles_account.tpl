{include file="common/subheader.tpl" title=__("user_account_information")}

{if $uid == 1 || ($user_data|fn_check_user_type_admin_area && $user_data.user_id && "RESTRICTED_ADMIN"|defined) || $user_data.is_root == "Y" || $auth.is_root == "Y" || $user_data.user_id == $auth.user_id}
    <input type="hidden" name="user_data[status]" value="A" />
    <input type="hidden" name="user_data[user_type]" value="{$user_data.user_type}" />
{/if}

<div class="control-group">
    <label for="email" class="control-label cm-required cm-email">{__("email")}:</label>
    <div class="controls">
        <input type="text" id="email" name="user_data[email]" class="input-large" size="32" maxlength="128" value="{$user_data.email}" />
    </div>
</div>

<div class="control-group">
    <label for="password1" class="control-label cm-required">{__("password")}:</label>
    <div class="controls">
        <input type="password" id="password1" name="user_data[password1]" class="input-large cm-autocomplete-off" size="32" maxlength="32" value="{if $runtime.mode == "update"}            {/if}" />
    </div>
</div>

<div class="control-group">
    <label for="password2" class="control-label cm-required">{__("confirm_password")}:</label>
    <div class="controls">
        <input type="password" id="password2" name="user_data[password2]" class="input-large cm-autocomplete-off" size="32" maxlength="32" value="{if $runtime.mode == "update"}            {/if}" />
    </div>
</div>

{if ($uid != 1 || "RESTRICTED_ADMIN"|defined) && $user_data.user_id != $auth.user_id}
    {if $runtime.company_id && $user_data.is_root != "Y" || !$user_data|fn_check_user_type_admin_area || !$user_data.user_id || ($user_data.company_id && !$auth.company_id)}

        {include file="common/select_status.tpl" input_name="user_data[status]" id="user_data" obj=$user_data hidden=false display=$display}

        {$_u_type=$smarty.request.user_type|default:$user_data.user_type}

        <input type="hidden" name="user_data[user_type]" value="{$_u_type}" />

        <div class="control-group">
            <label class="control-label" for="tax_exempt">{__("tax_exempt")}:</label>
            <input type="hidden" name="user_data[tax_exempt]" value="N" />
            <div class="controls">
                <input id="tax_exempt" type="checkbox" name="user_data[tax_exempt]" value="Y" {if $user_data.tax_exempt == "Y"}checked="checked"{/if} />
            </div>
        </div>

    {/if}
{/if}

<div class="control-group">
    <label class="control-label" for="user_language">{__("language")}</label>
    <div class="controls">
    <select name="user_data[lang_code]" id="user_language">
        {foreach from=$languages item="language" key="lang_code"}
            <option value="{$lang_code}" {if $lang_code == $user_data.lang_code}selected="selected"{/if}>{$language.name}</option>
        {/foreach}
    </select>
    </div>
</div>
