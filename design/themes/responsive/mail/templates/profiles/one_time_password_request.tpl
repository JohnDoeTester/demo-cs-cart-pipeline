{include file="common/letter_header.tpl"}
{__("email.one_time_password.message", ["[storefront_url]" => $storefront_url, "[password]" => $password]) nofilter}
{include file="common/letter_footer.tpl"}