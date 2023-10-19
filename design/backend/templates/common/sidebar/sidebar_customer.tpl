{if $user_data}

    {if $user_data.firstname || $user_data.lastname}
        {$customer_name = "`$user_data.firstname` `$user_data.lastname`"}
    {elseif $user_data.name}
        {$customer_name = $user_data.name}
    {else}
        {$customer_name = $user_default_name|default:__("customer")}
    {/if}
    
    <div class="sidebar-row sidebar-customer">
        <h6>{__("customer_info_sidebar")}</h6>
        <ul class="unstyled">
            {hook name="common:sidebar_customer"}
                <li>
                    {if ($user_data.firstname || $user_data.lastname || $user_data.name) && $user_data.user_id > 0}
                        <a href="{"profiles.update&user_id=`$user_data.user_id`"|fn_url}">
                            {$customer_name nofilter}{if $user_data.city}, {$user_data.city}{/if}{if $user_data.country}, {$user_data.country}{/if}
                        </a>
                    {else}
                        <span class="sidebar-customer__customer-name">
                            {$customer_name nofilter}{if $user_data.city}, {$user_data.city}{/if}{if $user_data.country}, {$user_data.country}{/if}
                        </span>
                    {/if}
                    <span>
                        {hook name="common:sidebar_customer_icon"}
                        {/hook}
                    </span>
                </li>

                {if $user_data.email}
                    <li>
                        <a href="mailto:{$user_data.email}">
                            {$user_data.email}
                        </a>
                    </li>
                {/if}

                {if $user_data.s_city || $user_data.s_country}
                    <li>
                        <span>{$user_data.s_city}</span><span>,</span>
                        <span>{$user_data.s_country}</span>
                    </li>
                {/if}

                {if $user_data.ip_address}
                    <li>
                        <span>
                            {__("ip_address")}:
                        </span>
                        <span>
                            {$user_data.ip_address}
                        </span>
                    </li>
                {/if}

                {if $user_data.phone}
                    <li>
                        <span>
                            {__("phone")}:
                        </span>
                        <a href="tel:{$user_data.phone}">
                            <bdi>{$user_data.phone}</bdi>
                        </a>
                    </li>
                {/if}
            {/hook}
        </ul>
    </div>
{/if}
