{*
    $helpfulness                    array                               Helpfulness
    $size                           string                              Size
*}

{if $helpfulness}

    <span title="{__("product_reviews.helpfulness")}: {$helpfulness.helpfulness}
{__("product_reviews.vote_up")}: {$helpfulness.vote_up}
{__("product_reviews.vote_down")}: {$helpfulness.vote_down}"
        class="
            {if $size === "small"}
                slashed-child
            {else}
                spaced-child
            {/if}
        "
    >

        <span>
            {if $size !== "small"}
                {include_ext file="common/icon.tpl"
                    class="icon-thumbs-up muted"
                }
            {/if}
            <span class="text-success">
                {if $helpfulness.vote_up > 0}+{/if}{$helpfulness.vote_up}
            </span>
        </span>

        <span>
            {if $size !== "small"}
                {include_ext file="common/icon.tpl"
                    class="icon-thumbs-down muted"
                }
            {/if}
            <span class="text-error">
                {if $helpfulness.vote_down > 0}−{/if}{$helpfulness.vote_down}
            </span>
        </span>

    </span>
{/if}
