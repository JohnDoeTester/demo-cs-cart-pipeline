{if !$addon.is_core_addon && $addon.identified}
    <div class="sidebar-row">
        <h6>{__("cscart_marketplace")}</h6>

        {* Addon rating *}
        <div class="control-group sidebar__stats">
            <label class="control-label sidebar__label" for="addon_rating">{__("rating")}:</label>
            <div class="controls sidebar__controls">

                {if $reviews}
                    {include file="views/addons/components/rating/stars.tpl"
                        rating=$average_rating
                        total_reviews=$reviews|count
                        link=true
                    }
                {else}
                    <span class="muted">
                        {__("addons.no_reviews")}
                    </span>
                {/if}

            </div>
        </div>

        {* Supplier (Developer) *}
        <div class="control-group sidebar__stats">
            <label class="control-label sidebar__label">{__("developer")}:</label>
            <div class="controls sidebar__controls">
                <a href="{$addon_developer_url}"
                    target="_blank"
                >
                    {$addon.supplier}
                </a>
            </div>
        </div>

        {* Marketplace category) *}
        <div class="control-group sidebar__stats">
            <label class="control-label sidebar__label">{__("category")}:</label>
            <div class="controls sidebar__controls">
                <a href="{$addon_category_url}"
                    target="_blank"
                >
                    {$addon.category_name|default:__("addons.other_category")}
                </a>
            </div>
        </div>

        {* Marketplace link *}
        <div class="control-group">
            <p>
                <a href="{$addon_marketplace_page}" target="_blank">{__("view_in_marketplace")}</a>
            </p>
        </div>
    </div>
{/if}
