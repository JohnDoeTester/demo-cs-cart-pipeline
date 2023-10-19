{if $addons.product_variations.status === "ObjectStatuses::ACTIVE"|enum}
    <div class="control-group setting-wide product_reviews">
        <label for="split_reviews_for_variations_as_separate_products" class="control-label ">
            {__("product_reviews.split_reviews_for_variations_as_separate_products")}:
        </label>

        <div class="controls">
            <input type="hidden" name="split_reviews_for_variations_as_separate_products" value="{"YesNo::NO"|enum}">
            <input id="split_reviews_for_variations_as_separate_products"
                   name="split_reviews_for_variations_as_separate_products"
                   type="checkbox"
                   value="{"YesNo::YES"|enum}"
                   {if $split_reviews_for_variations_as_separate_products === "YesNo::YES"|enum}
                       checked="checked"
                   {/if}
            >
        </div>
    </div>
{/if}
