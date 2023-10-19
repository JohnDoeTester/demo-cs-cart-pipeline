{$devices = [
    "" => [
        "name" => __("block_manager.view_layout.reset_filter"),
        "icon_class" => "",
        "btn_class" => "btn btn-primary cm-reset-device-availability"
    ],
    "phone" => [
        "name" => "<span class=\"mobile-hidden\">{__("block_manager.view_layout.phone")}</span>",
        "icon_class" => "icon-mobile-phone",
        "btn_class" => "btn cm-switch-device-availability"
    ],
    "tablet" => [
        "name" => "<span class=\"mobile-hidden\">{__("block_manager.view_layout.tablet")}</span>",
        "icon_class" => "icon-tablet",
        "btn_class" => "btn cm-switch-device-availability"
    ],
    "desktop" => [
        "name" => "<span class=\"mobile-hidden\">{__("block_manager.view_layout.desktop")}</span>",
        "icon_class" => "icon-desktop",
        "btn_class" => "btn cm-switch-device-availability"
    ]
]}

<div class="device-switch-wrap" id="device_switch">
    <div class="btn-group device-switch">
        {foreach $devices as $device_id => $device}
            {btn type="text"
                text=$device.name
                icon=$device.icon_class
                icon_first=true
                raw=true
                class="device-switch__device {$device.btn_class}"
                data=["data-ca-device-availability-device" => $device_id]
            }
        {/foreach}
    </div>
<!--device_switch--></div>
