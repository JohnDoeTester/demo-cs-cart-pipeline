{if !$addon.is_core_addon && $addon.identified && !$personal_review}
    <div class="sidebar-row marketplace">
        {include file="views/addons/components/rating/enjoying_addon_notification.tpl"
            id="addons_write_review_sidebar"
        }
    </div>
{/if}
