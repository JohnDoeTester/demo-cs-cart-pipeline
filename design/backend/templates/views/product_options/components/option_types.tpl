{$selectbox = "ProductOptionTypes::SELECTBOX"|enum}
{$radio_group = "ProductOptionTypes::RADIO_GROUP"|enum}
{$checkbox = "ProductOptionTypes::CHECKBOX"|enum}
{$input = "ProductOptionTypes::INPUT"|enum}
{$text = "ProductOptionTypes::TEXT"|enum}
{$file = "ProductOptionTypes::FILE"|enum}

{strip}
{if $display == "view"}
    {if $value == $selectbox}{__("selectbox")}
    {elseif $value == $radio_group}{__("radiogroup")}
    {elseif $value == $checkbox}{__("checkbox")}
    {elseif $value == $input}{__("text")}
    {elseif $value == $text}{__("textarea")}
    {elseif $value == $file}{__("file")}
    {/if}
{else}

    {if $value && $option_id}
	{if $value == $selectbox || $value == $radio_group}
	    {$app_types = "{$selectbox}{$radio_group}"}
	{elseif $value == $input || $value == $text}
	    {$app_types = "{$input}{$text}"}
	{elseif $value == $checkbox}
	    {$app_types = "{$checkbox}"}
	{else}
	    {$app_types = "{$file}"}
	{/if}
    {else}
	{$app_types = ""}
    {/if}
    
    <select id="{$tag_id}" name="{$name}" {if $check}onchange="fn_check_option_type(this.value, this.id);"{/if}>
	{if !$app_types || ($app_types && $app_types|strpos:$selectbox !== false)}
	    <option value="{$selectbox}" {if $value == $selectbox} selected="selected"{/if}>{__("selectbox")}</option>
	{/if}
	{if !$app_types || ($app_types && $app_types|strpos:$radio_group !== false)}
	    <option value="{$radio_group}" {if $value == $radio_group} selected="selected"{/if}>{__("radiogroup")}</option>
	{/if}
	{if !$app_types || ($app_types && $app_types|strpos:$checkbox !== false)}
	    <option value="{$checkbox}" {if $value == $checkbox} selected="selected"{/if}>{__("checkbox")}</option>
	{/if}
	{if !$app_types || ($app_types && $app_types|strpos:$input !== false)}
	    <option value="{$input}" {if $value == $input} selected="selected"{/if}>{__("text")}</option>
	{/if}
	{if !$app_types || ($app_types && $app_types|strpos:$text !== false)}
	    <option value="{$text}" {if $value == $text} selected="selected"{/if}>{__("textarea")}</option>
	{/if}
	{if !$app_types || ($app_types && $app_types|strpos:$file !== false)}
	    <option value="{$file}" {if $value == $file} selected="selected"{/if}>{__("file")}</option>
	{/if}
    </select>

{/if}
{/strip}
