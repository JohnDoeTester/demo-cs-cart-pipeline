{*
    $product_id
    $auth
    $user_info
    $product_review_data
    $_country
    $settings
    $countries
    $code
    $country
*}

{$_country=($auth.user_id) ? $user_data.s_country : ""}
{$user_name=($user_info.lastname) ? "`$user_info.firstname` `$user_info.lastname`" : $user_info.firstname}

<div class="ty-product-review-new-product-review-customer-profile">

    <div class="ty-product-review-new-product-review-customer-profile__name ty-width-full">
        <label class="cm-required cm-trim hidden ty-product-review-new-product-review-customer-profile__name-label"
            data-ca-product-review="newProductReviewCustomerProfileNameLabel"
            for="product_review_name_{$product_id}"
        >
            {__("product_reviews.first_and_last_name")}
        </label>
        <input type="text"
            id="product_review_name_{$product_id}"
            name="product_review_data[name]"
            value="{if $product_review_data.name}{$product_review_data.name}{else}{$user_name}{/if}"
            class="ty-product-review-new-product-review-customer-profile__name-input ty-input-text-full"
            data-ca-product-review="newProductReviewCustomerProfileNameInput"
            data-ca-product-review-label-required="{__("product_reviews.first_and_last_name")} *"
            data-ca-product-review-label="{__("product_reviews.first_and_last_name")}"
            placeholder="{__("product_reviews.first_and_last_name")} *"
            title="{__("product_reviews.first_and_last_name")} *"
        />
    </div>

    {if $addons.product_reviews.review_ask_for_customer_location !== "none"}
        <div class="ty-product-review-new-product-review-customer-profile__location">
            {if $addons.product_reviews.review_ask_for_customer_location === "city"}
                <div class="ty-product-review-new-product-review-customer-profile__city ty-width-full">
                    <label class="cm-required cm-trim hidden ty-product-review-new-product-review-customer-profile__city-label"
                        data-ca-product-review="newProductReviewCustomerProfileCityLabel"
                        for="product_review_city_{$product_id}"
                    >
                        {__("city")}
                    </label>
                    <input type="text"
                        id="product_review_city_{$product_id}"
                        name="product_review_data[city]"
                        value="{if $auth.user_id}{$user_data.s_city}{/if}"
                        class="ty-product-review-new-product-review-customer-profile__city-input ty-input-text-full"
                        placeholder="{__("city")} *"
                        title="{__("city")} *"
                        data-ca-product-review="newProductReviewCustomerProfileCityInput"
                        data-ca-product-review-label-required="{__("city")} *"
                        data-ca-product-review-label="{__("city")}"
                    />
                </div>
            {elseif $addons.product_reviews.review_ask_for_customer_location === "country"}
                <div class="ty-product-review-new-product-review-customer-profile__country ty-width-full">
                    <label class="cm-required hidden ty-product-review-new-product-review-customer-profile__country-label"
                        data-ca-product-review="newProductReviewCustomerProfileCountryLabel"
                        for="product_review_country_code_{$product_id}"
                    >
                        {__("country")}
                    </label>
                    <select id="product_review_country_code_{$product_id}"
                        class="ty-product-review-new-product-review-customer-profile__country-input ty-input-text-full ty-input-height cm-country cm-location-shipping"
                        name="product_review_data[country_code]"
                        title="{__("country")} *"
                        data-ca-product-review="newProductReviewCustomerProfileCountryInput"
                        data-ca-product-review-label-required="{__("country")} *"
                        data-ca-product-review-label="{__("country")}"
                        data-ca-product-review-option-required="— {__("select_country")} — *"
                        data-ca-product-review-option="— {__("select_country")} —"
                    >
                        <option value="">— {__("select_country")} — *</option>
                        {foreach $countries as $code => $country}
                            <option value="{$code}" {if $code === $_country} selected{/if}>{$country}</option>
                        {/foreach}
                    </select>
                </div>
            {/if}
        </div>
    {/if}
</div>
