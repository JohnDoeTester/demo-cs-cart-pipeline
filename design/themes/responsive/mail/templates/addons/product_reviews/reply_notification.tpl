{include file="common/letter_header.tpl"}

{__("hello")}, {$user_data.firstname} {$user_data.lastname}<br /><br />

{__("product_reviews.text_new_reply_notification")}:&nbsp;<a href="{$product_url|replace:'&amp;':'&'}">{$product_data.product}</a>
<br /><br />

<b>{__("product_reviews.reply")}</b>:<br />
{$product_review_data.reply.reply|nl2br}
<br /><br />

{__("view")}:<br />
<a href="{$product_url|replace:'&amp;':'&'}">{$product_url|replace:'&amp;':'&'|puny_decode}</a>

{include file="common/letter_footer.tpl"}