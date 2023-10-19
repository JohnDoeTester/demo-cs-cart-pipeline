{include file="common/widget_copy.tpl"
    widget_copy_title=__("tip")
    widget_copy_text=__("google_sitemap.text_auto_regenerate")
    widget_copy_code_text = fn_get_console_command("php /path/to/cart/", $config.admin_index, [
        "dispatch" => "xmlsitemap.generate"
    ])
}
