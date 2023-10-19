{*
    $product_review_reply           array                               Product review reply
    $NAME_CHARACTERS_THRESHOLD      int                                 Name characters threshold
*}

{strip}
    {$NAME_CHARACTERS_THRESHOLD = 30}

    {if $product_review_reply.reply_company}
        <a href="{"companies.update?company_id=`$product_review_reply.reply_company_id`"|fn_url}" {""}
            title="{$product_review_reply.reply_company}"
        >{$product_review_reply.reply_company|truncate:$NAME_CHARACTERS_THRESHOLD:"...":true}</a>
    {else}
        <span>{__("administrator")}</span>
    {/if}
{/strip}
