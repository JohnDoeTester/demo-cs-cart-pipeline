{$show_menu_caret = $show_menu_caret|default:true}

<li class="dropdown dropdown-top-menu-item hover-show--disabled notifications-center__opener-wrapper cm-dropdown-skip-processing">
    <a class="dropdown-toggle dropdown-top-menu-item-link" data-toggle="dropdown">
        <span
            class="icon icon-bell-alt cc-notify" 
            title="{__("notifications_center.notifications")}"
            data-ca-notifications-center-counter
        >
        </span>
        <span class="" ></span>
        {if $show_menu_caret}
            <b class="caret"></b>
        {/if}
    </a>
    <ul class="dropdown-menu pull-right notifications-center__root" data-ca-notifications-center-root>
        <div class="cc-dropdown">
            <div class="cc-dropdown__title-wrapper" text="{__("notifications_center.notifications")}">
                <span class="cc-dropdown__title">{__("notifications_center.notifications")}</span>
            </div>
            <div class="cc-all-read">
                <div class="cc-all-read--inner">{__("loading")}</div>
            </div>
        </div>
    </ul>
</li>

<script>
(function (_, $) {
    _.tr({
        loading: '{__("loading")|escape:"javascript"}',
        showMore: '{__("show_more")|escape:"javascript"}',
        showLess: '{__("show_less")|escape:"javascript"}',
        "notifications_center.noData": '{__("notifications_center.no_notifications")|escape:"javascript"}',
        "notifications_center.notifications": '{__("notifications_center.notifications")|escape:"javascript"}',
        "notifications_center.markAllAsRead": '{__("notifications_center.mark_all_as_read")|escape:"javascript"}'
    });

    $.ceEvent('one', 'ce.commoninit', function (context) {
        $(context).find('.notifications-center__opener-wrapper a').on('click', function () {
            if (Tygh.ceNotificationsCenterInited) {
                $.ceEvent('trigger', 'ce.notifications_center.enabled');
            }
        });
    });
})(Tygh, Tygh.$);
</script>
