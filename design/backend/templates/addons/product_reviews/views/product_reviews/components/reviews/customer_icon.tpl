{*
    $user_data                      array                               User data
*}

{if $user_data.is_buyer || $user_data.user_id}

    {if $user_data.is_buyer === "YesNo::YES"|enum}
        {include_ext file="common/icon.tpl"
            class="icon-ok-sign muted"
            title=__("product_reviews.verified_purchase")
        }
    {/if}

    {if $user_data.is_anon}
        {include_ext file="common/icon.tpl"
            class="icon-eye-close muted"
            title=__("anonymous")
        }
    {/if}

{/if}
