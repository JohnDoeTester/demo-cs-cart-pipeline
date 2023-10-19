{capture name="mainbox"}
    {$container_id = $smarty.request.container_id|default:"elfinder"}
    {$available_languages = [
        "de", "ar", "bg", "ca", "cs", "da", "el", "es",    "fa", "fo", "fr_CA", "fr",
        "he", "hr", "hu", "id", "it", "ja", "ko", "nl",    "no", "pl", "pt_BR", "ro",
        "ru", "si", "sk", "sl", "sr", "sv", "tr", "ug_CN", "uk", "vi", "zh_CN", "zh_TW"
    ]}
    {if $smarty.const.CART_LANGUAGE === "pt"}
        {$transform_lang_code = "pt_BR"}
    {elseif $smarty.const.CART_LANGUAGE === "ug"}
        {$transform_lang_code = "ug_CN"}
    {elseif $smarty.const.CART_LANGUAGE === "zh"}
        {$transform_lang_code = "zh_CN"}
    {else}
        {$transform_lang_code = $smarty.const.CART_LANGUAGE}
    {/if}

    <script>
    (function(_, $) {
        $.getScript('js/lib/elfinder/js/elfinder.min.js')
            .done(function () {
                $.loadCss(['js/lib/elfinder/css/elfinder.min.css']);
                $.loadCss(['js/lib/elfinder/css/theme.css']);

                {if $smarty.const.CART_LANGUAGE !== 'en' && $transform_lang_code|in_array:$available_languages}
		    $.getScript('js/lib/elfinder/js/i18n/elfinder.{$transform_lang_code}.js')
			.then(null, function() { return $.getScript('js/lib/elfinder/js/i18n/elfinder.LANG.js'); })
			.done(fn_init_elfinder);
                {else}
                    fn_init_elfinder();
                {/if}
            });

        function fn_init_elfinder() {
            var w = $.getWindowSizes();
            var options = $.extend(_.fileManagerOptions, {
                url: fn_url('elf_connector.manage?start_path={$smarty.request.path}&security_hash=' + _.security_hash),
                height: w.view_height - 190
            });
            $('#{$container_id}').elfinder(options);
        }
    }(Tygh, Tygh.$))
    </script>

    <div id={$container_id}></div>

{/capture}

{if $smarty.request.in_popup}
    {$smarty.capture.mainbox nofilter}
{else}
    {include file="common/mainbox.tpl" content=$smarty.capture.mainbox title=__("file_editor") buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons sidebar=$smarty.capture.sidebar sidebar_position="left"}
{/if}
