<li class="dropdown dropdown-top-menu-item notifications-center--mobile notifications-center__opener-wrapper">
    <a class="dropdown-toggle dropdown-top-menu-item-link nav__menu-item cm-combination" data-toggle="dropdown" id="sw_select_{$selected_id}_wrap_{$suffix}" data-ca-mobile-menu-title="{__("notifications")}">
        {__("notifications")}
        <span class="cc-notify-counter-container" data-ca-notifications-center-counter-mobile>3</span>
    </a>

    <ul class="dropdown-menu cm-select-list pull-right notifications-center__root notifications-center__root--mobile" data-ca-notifications-center-mobile-attribute="data-ca-notifications-center-root-mobile">
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
    (function(_, $) {
        _.tr({
            loading: '{__("loading")|escape:"javascript"}',
            showMore: '{__("show_more")|escape:"javascript"}',
            showLess: '{__("show_less")|escape:"javascript"}',
            "notifications_center.noData": '{__("notifications_center.no_notifications")|escape:"javascript"}',
            "notifications_center.notifications": '{__("notifications_center.notifications")|escape:"javascript"}'
        });

        $.ceEvent('on', 'ce.mobile_menu.dropdownMenu_created', function() {
            if (Tygh.ceNotificationsCenterInited) {
                const $elem =  $('.overlayed-mobile-menu-container .notifications-center__root--mobile');
                $elem.attr($elem.data('caNotificationsCenterMobileAttribute'), '');
                
                $.ceEvent('trigger', 'ce.notifications_center.mobile_enabled');
            }
        });

        $.ceEvent('on', 'ce.commoninit', function(context) {
            if ($(context).find('[data-ca-notifications-mark]').length && Tygh.ceNotificationsCenterInited) {
                $.ceEvent('trigger', 'ce.notifications_center.notifications_mark_reload');
            }
        });
    })(Tygh, Tygh.$);
</script>