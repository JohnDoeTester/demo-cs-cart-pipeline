{include file="common/letter_header.tpl"}

{__("hello")},<br /><br />

{__("product_reviews.text_new_post_notification")}:&nbsp;<a href="{$product_url|replace:'&amp;':'&'}">{$product_data.product}</a>
<br /><br />
<b>{__("person_name")}</b>:&nbsp;{if $product_review_data.name}{$product_review_data.name}{else}{__("customer")}{/if}<br />

<b>{__("product_reviews.rating")}</b>:&nbsp;{if $product_review_data.rating_value == "5"}{__("product_reviews.excellent")}{elseif $product_review_data.rating_value == "4"}{__("product_reviews.very_good")}{elseif $product_review_data.rating_value == "3"}{__("product_reviews.average")}{elseif $product_review_data.rating_value == "2"}{__("product_reviews.fair")}{elseif $product_review_data.rating_value == "1"}{__("product_reviews.poor")}{/if}
<br />

{if $review_fields === 'advanced'}
    {if $product_review_data.message.advantages}
    <b>{__("product_reviews.advantages")}</b>:<br />
    {$product_review_data.message.advantages|nl2br}
    <br />
    {/if}

    {if $product_review_data.message.disadvantages}
    <b>{__("product_reviews.disadvantages")}</b>:<br />
    {$product_review_data.message.disadvantages|nl2br}
    <br />
    {/if}
{/if}

{if $product_review_data.message.comment}
<b>{__("product_reviews.comment")}</b>:<br />
{$product_review_data.message.comment|nl2br}
<br /><br />
{/if}

{if $product_review_data.status === 'D'}
<b>{__("product_reviews.text_approval_notice")}</b>
<br />
{/if}
{__("view")}:<br />
<a href="{$product_review_url|replace:'&amp;':'&'}">{$product_review_url|replace:'&amp;':'&'|puny_decode}</a>

{include file="common/letter_footer.tpl"}