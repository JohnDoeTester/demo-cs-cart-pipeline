{* Total reviews count *}
{$total_reviews = $reviews|count scope=parent}

<div class="hidden cm-hide-save-button" id="content_reviews">
    <div class="form-horizontal form-edit">

        {* Enjoying add-on? *}
        {if !$addon.is_core_addon && $addon.identified && !$personal_review}
            <div class="alert alert-block alert-info">
                {include file="views/addons/components/rating/enjoying_addon_notification.tpl"
                    title_full=true
                    id="addons_write_review_notification"
                }
            </div>
        {/if}

        {* Reviews *}
        {include file="common/subheader.tpl" title=__("addon_reviews") target="#addon_reviews"}
        <div id="addon_reviews" class="collapse in collapse-visible">

            {include file="views/addons/components/rating/addon_rating_overview.tpl"
                ratings_stats=$addon_reviews_rating_stats
                total_addon_reviews=$total_reviews
                average_rating=$average_rating
            }

            {include file="views/addons/components/addons/addon_reviews.tpl"
                reviews=$reviews
                total_addon_reviews=$total_reviews
            }

        </div>

    </div>
<!--content_reviews--></div>
