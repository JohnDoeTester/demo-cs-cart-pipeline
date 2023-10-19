{*
    $user_data
    $product_id
    $auth
    $user_info
    $post_redirect_url
    $product_review_data
    $countries
*}

<div class="control-group ty-product-review-new-product-review-customer__header">
    <label for="product_review_name_{$product_id}"
        class="control-label cm-required ty-product-review-new-product-review-customer__title ty-strong"
    >
        {__("customer")}:
    </label>

    <div class="controls">
        {$_country=($auth.user_id) ? $user_data.s_country : ""}
        {$user_name=($user_info.lastname) ? "`$user_info.firstname` `$user_info.lastname`" : $user_info.firstname}

        <div class="ty-product-review-new-product-review-customer-profile">
            <div class="ty-product-review-new-product-review-customer-profile__name ty-width-full">
                <input type="text"
                       id="product_review_name_{$product_id}"
                       name="product_review_data[name]"
                       value="{if $product_review_data.name}{$product_review_data.name}{else}{$user_name}{/if}"
                       class="ty-product-review-new-product-review-customer-profile__name-input input-full"
                />
            </div>
        </div>
    </div>
</div>

<div class="control-group ty-product-review-new-product-review-customer__header">
    <div class="controls ty-control-group ty-product-review-new-product-review-additional__write-anonymously">
        <label class="ty-product-review-new-product-review-additional__write-anonymously-label">
            <input type="checkbox"
                   name="product_review_data[is_anon]"
                   value="{"YesNo::YES"|enum}"
                   class="ty-product-review-new-product-review-additional__write-anonymously-checkbox"
            >
            <span class="ty-product-review-new-product-review-additional__write-anonymously-text">
                {__("product_reviews.hide_name")}
            </span>
        </label>
    </div>
</div>


{if $addons.product_reviews.review_ask_for_customer_location !== "none"}
    <div class="control-group ty-product-review-new-product-review-customer__header">
        {if $addons.product_reviews.review_ask_for_customer_location === "city"}
            <label for="product_review_city_{$product_id}"
                class="control-label ty-product-review-new-product-review-customer__title ty-strong cm-required"
            >
                {__("city")}:
            </label>
        {elseif $addons.product_reviews.review_ask_for_customer_location === "country"}
            <label for="product_review_country_code_{$product_id}"
                   class="control-label ty-product-review-new-product-review-customer__title ty-strong cm-required"
            >
                {__("country")}:
            </label>
        {/if}

        <div class="controls">
            <div class="ty-product-review-new-product-review-customer-profile">
                <div class="ty-product-review-new-product-review-customer-profile__location">
                    {if $addons.product_reviews.review_ask_for_customer_location === "city"}
                        <div class="ty-product-review-new-product-review-customer-profile__city ty-width-full">
                            <input type="text"
                                   id="product_review_city_{$product_id}"
                                   name="product_review_data[city]"
                                   value="{if $auth.user_id}{$user_data.s_city}{/if}"
                                   class="ty-product-review-new-product-review-customer-profile__city-input input-full"
                            />
                        </div>
                    {elseif $addons.product_reviews.review_ask_for_customer_location === "country"}
                        <div class="ty-product-review-new-product-review-customer-profile__country ty-width-full">
                            <select id="product_review_country_code_{$product_id}"
                                    class="ty-input-height cm-country cm-location-shipping input-full"
                                    name="product_review_data[country_code]"
                            >
                                <option value="">— {__("select_country")} — *</option>
                                {foreach $countries as $code => $country}
                                    <option value="{$code}" {if $code === $_country} selected{/if}>{$country}</option>
                                {/foreach}
                            </select>
                        </div>
                    {/if}
                </div>
            </div>
        </div>
    </div>
{/if}