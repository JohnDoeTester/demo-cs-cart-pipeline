{$is_demo_mode = $config.demo_mode|default:false}
{$show_theme_editor = (
    $smarty.const.AREA === "SiteArea::ADMIN_PANEL"|enum
    && $auth.act_as_area && $auth.act_as_area === "UserTypes::VENDOR"|enum
    || $is_demo_mode
) scope=parent}
