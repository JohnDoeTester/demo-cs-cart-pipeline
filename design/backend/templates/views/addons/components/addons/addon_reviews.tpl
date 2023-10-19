{$reviews_displayed_number = $reviews_displayed_number|default:3}

<div class="addons-addon-reviews">

    {if $reviews}
        <div class="addons-addon-reviews__grid">
            {foreach $reviews as $addon_key => $addon_review}

                {if $addon_key === $reviews_displayed_number}
                    {break}
                {/if}

                <div class="addons-addon-reviews__item">
                    {include file="views/addons/components/addons/addon_post.tpl"
                        rating_value=$addon_review.rating_value
                        message=$addon_review.message
                        timestamp=$addon_review.timestamp
                        user_data=[
                            name => $addon_review.name
                        ]
                    }
                </div>

            {/foreach}
        </div>


        {if $total_addon_reviews > $reviews_displayed_number}

            <div>
                
                {include file="buttons/button.tpl"
                    but_text=__("addons.show_all_reviews")
                    but_role="action"
                    but_id="on_addon_reviews_grid"
                    but_meta="cm-combination"
                }

                <div id="addon_reviews_grid" name="addon_reviews_grid" class="hidden">

                    <div class="addons-addon-reviews__grid">

                        {foreach $reviews as $addon_key => $addon_review}

                            {if $addon_key < $reviews_displayed_number}
                                {continue}
                            {/if}

                            <div class="addons-addon-reviews__item">
                                {include file="views/addons/components/addons/addon_post.tpl"
                                    rating_value=$addon_review.rating_value
                                    message=$addon_review.message
                                    timestamp=$addon_review.timestamp
                                    user_data=[
                                        name => $addon_review.name
                                    ]
                                }
                            </div>

                        {/foreach}

                    </div>

                    {include file="buttons/button.tpl"
                        but_text=__("addons.hide_all_reviews")
                        but_role="action"
                        but_id="off_addon_reviews_grid"
                        but_meta="cm-combination"
                    }

                </div>
            </div>
        {/if}

    {else}

        <p class="no-items">{__("no_data")}</p>

    {/if}

</div>
