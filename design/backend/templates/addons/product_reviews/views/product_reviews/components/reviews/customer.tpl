{*
    $user_data                      array                               User data
    $show_customer                  bool                                Show customer
    $customer_name                  string                              Customer name
    $NAME_CHARACTERS_THRESHOLD      int                                 Name characters threshold
*}

{$show_customer = $show_customer|default:true}
{$customer_name = $user_data.name|truncate:$NAME_CHARACTERS_THRESHOLD:"...":true}
{$NAME_CHARACTERS_THRESHOLD = 30}

<span>
    {if $show_customer && $user_data.is_authorized}
        <a href="{"profiles.update&user_id=`$user_data.user_id`"|fn_url}"
            title="{$user_data.name}"
        >
            {$customer_name nofilter}
        </a>

    {elseif $show_customer && $user_data.name}
        <span title="{$user_data.name}">
            {$customer_name nofilter}
        </span>

    {elseif $show_customer}
        <span class="muted">
            {__("anonymous")}
        </span>

    {/if}

    {include file="addons/product_reviews/views/product_reviews/components/reviews/customer_icon.tpl"
        user_data=$user_data
    }
</span>
