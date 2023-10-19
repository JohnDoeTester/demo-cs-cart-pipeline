{style src="addons/vendor_panel_configurator/styles.less"}

{if $smarty.const.ACCOUNT_TYPE === "vendor"}
    {include file="addons/vendor_panel_configurator/config.tpl"}

    {style src="addons/vendor_panel_configurator/simple_vendor_panel/index.less"}
    {capture name="styles"}
        {$calculatedSaturation = ($isGrayMainColor) ? "0%" : "100%"}

        @mainColor: {$mainColor};
        @tableBackgroundHover: spin(hsl(hue({$mainColor}), {$calculatedSaturation}, 98%), 5);
        @wellBackground: spin(hsl(hue({$mainColor}), {$calculatedSaturation}, 98%), 5);
        @menuSidebarColor: {$menuSidebarColor};
        @menuSidebarColorActive: spin(hsla(hue({$mainColor}), {$calculatedSaturation}, 40%, 0.1), 5);
        @menuSidebarBg: {$menuSidebarBg};
    {/capture}
    {style content=$smarty.capture.styles type="less"}
{/if}

{if $runtime.customization_mode.theme_editor}
    {style src="addons/vendor_panel_configurator/theme_editor.less"}
    {style src="../../themes/responsive/css/tygh/theme_editor.css"}
{/if}
