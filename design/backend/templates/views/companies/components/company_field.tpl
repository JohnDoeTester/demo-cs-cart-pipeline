{$required = $required|default:false}
{if $runtime.company_id && (!$selected || "MULTIVENDOR"|fn_allowed_for) &&  !$disable_company_picker}
    {$selected = $runtime.company_id}
{/if}
{$company_field_name = $company_field_name|default: __("owner_company")}

{if !$selected}
    {if $zero_company_id_name_lang_var || $force_zero_company_id}
        {$selected = ($required) ? "" : "0"}
    {else}
        {if "MULTIVENDOR:ULTIMATE"|fn_allowed_for && !empty($auth.storefront_id)}
            {$selected = fn_get_default_company_id_for_storefront($auth.storefront_id)}
        {else}
            {$selected = fn_get_default_company_id()}
        {/if}
    {/if}
{/if}

{capture name="c_body"}
    <input type="hidden" class="cm-no-failed-msg" name="{$name}" id="{$id|default:"company_id"}" value="{$selected}">
    {if !$runtime.simple_ultimate}
        {if $runtime.company_id || $disable_company_picker}
            <div class="text-type-value">{$selected|fn_get_company_name:$zero_company_id_name_lang_var}</div>
        {else}
            <div class="text-type-value ajax-select-wrap {$meta}">
                {if $zero_company_id_name_lang_var}
                    {$url_extra = "&show_all=Y&default_label=`$zero_company_id_name_lang_var`"}
                    {if $required}
                        {$url_extra = "`$url_extra`&search=Y"}
                    {/if}
                {/if}
                {include file="common/ajax_select_object.tpl"
                    data_url="companies.get_companies_list?onclick=`$onclick`$url_extra"
                    text=$selected|fn_get_company_name:$zero_company_id_name_lang_var
                    result_elm=$id|default:"company_id"
                    id="`$id`_selector"
                    js_action=$js_action
                }
            </div>
        {/if}
    {/if}
{/capture}

{if !$runtime.simple_ultimate}
    {if !$no_wrap}
        <div class="control-group">
            <label class="control-label {if $required}cm-required{/if}" for="{$id|default:"company_id"}">{$company_field_name}</label>
            <div class="controls">
                {$smarty.capture.c_body nofilter}
                {if $tooltip}
                    <p class="muted description">{$tooltip nofilter}</p>
                {/if}
            </div>
        </div>
    {else}
        {$smarty.capture.c_body nofilter}
    {/if}
{else}
    {$smarty.capture.c_body nofilter}
{/if}