{$images = array_filter($promotions|array_column:"image")}
{$image = $images|end}

{if !empty($image)}
    {$promotion_image_height = $image.icon.image_y}
    {$promotion_image_width  = $image.icon.image_x}
{/if}

<div class="grid-list ty-grid-promotions">
    {hook name="promotions:promotion_list"}
    {if $promotions}
        {foreach $promotions as $promotion_id => $promotion}
            {hook name="promotions:list_item"}
                {if $promotion_id !== "chains"}
                    <div class="ty-column3">
                        <div class="ty-grid-list__item ty-grid-promotions__item">
                            {if $promotion.image}
                                {include file="common/image.tpl"
                                    images=$promotion.image
                                    image_id="promotion_image"
                                    class="ty-grid-promotions__image"
                                }
                            {/if}
                        <div class="ty-grid-promotions__content">
                            {if $promotion.to_date}
                                <div class="ty-grid-list__available">
                                    {__("avail_till")}: {$promotion.to_date|date_format:$settings.Appearance.date_format}
                                </div>
                            {/if}
                            {if "MULTIVENDOR"|fn_allowed_for && ($company_name || $promotion.company_id)}
                                    <div class="ty-grid-promotions__company">
                                        <a href="{"companies.products?company_id=`$promotion.company_id`"|fn_url}" class="ty-grid-promotions__company-link">
                                            {if $company_name}{$company_name}{else}{$promotion.company_id|fn_get_company_name}{/if}
                                        </a>
                                    </div>
                                {/if}
                                <h2 class="ty-grid-promotions__header">{$promotion.name}</h2>
                                {if $promotion.detailed_description || $promotion.short_description}
                                    <div class="ty-wysiwyg-content ty-grid-promotions__description">
                                        {$promotion.detailed_description|default:$promotion.short_description nofilter}
                                    </div>
                                {/if}
                            </div>
                        </div>
                    </div>
                {/if}
            {/hook}
        {/foreach}
    {else}
        <p class="ty-mlr-s ty-no-items">{__("text_no_active_promotions")}</p>
    {/if}
    {/hook}
</div>
{capture name="mainbox_title"}{__("active_promotions")}{/capture}
