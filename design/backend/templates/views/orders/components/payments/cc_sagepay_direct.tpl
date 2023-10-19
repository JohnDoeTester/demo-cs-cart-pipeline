{script src="js/tygh/sagepay_browser_settings.js"}

{include file= "views/orders/components/payments/cc.tpl"}

<input type="hidden" id="browser_user_agent_{$id_suffix}" name="browser_settings[user_agent]" value="">
<input type="hidden" id="browser_language_{$id_suffix}" name="browser_settings[language]" value="">
<input type="hidden" id="browser_color_depth_{$id_suffix}" name="browser_settings[color_depth]" value="">
<input type="hidden" id="browser_screen_height_{$id_suffix}" name="browser_settings[screen_height]" value="">
<input type="hidden" id="browser_screen_width_{$id_suffix}" name="browser_settings[screen_width]" value="">
<input type="hidden" id="browser_tz_{$id_suffix}" name="browser_settings[timezone]" value="">
<input type="hidden" id="browser_java_enabled_{$id_suffix}" name="browser_settings[java_enabled]" value="">
<input type="hidden" id="browser_js_enabled_{$id_suffix}" name="browser_settings[js_enabled]" value="">
