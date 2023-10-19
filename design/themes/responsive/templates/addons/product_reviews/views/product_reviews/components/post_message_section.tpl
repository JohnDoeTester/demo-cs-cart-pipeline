{*
    $message_title
    $message_body
*}

{if $message_title && $message_body}

    <dl class="ty-product-review-post-message-section ty-dl" data-ca-product-review="postMessageSection">

        {if $message_title}
            <dt class="ty-product-review-post-message-section__title ty-dt ty-strong">
                {$message_title nofilter}
            </dt>
        {/if}

        {if $message_body}
            <dd class="ty-product-review-post-message-section__body ty-dd">
                {include file="common/content_more.tpl"
                    text=$message_body|escape|nl2br
                }
            </dd>
        {/if}

    </dl>
{/if}
