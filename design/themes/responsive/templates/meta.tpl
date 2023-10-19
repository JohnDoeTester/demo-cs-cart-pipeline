{hook name="index:meta"}
{if $display_base_href}
<base href="{$config.current_location}/" />
{/if}
<meta http-equiv="Content-Type" content="text/html; charset={$smarty.const.CHARSET}" data-ca-mode="{$store_trigger}" />
<meta name="viewport" content="initial-scale=1.0, width=device-width" />
{hook name="index:meta_description"}
<meta name="description" content="{$meta_description|default:$location_data.meta_description|html_entity_decode:$smarty.const.ENT_COMPAT:"UTF-8"}" />
{/hook}
<meta name="keywords" content="{$meta_keywords|default:$location_data.meta_keywords}" />
<meta name="format-detection" content="telephone=no">
{/hook}
{$location_data.custom_html nofilter}
