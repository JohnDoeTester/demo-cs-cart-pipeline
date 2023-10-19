{if $app.antibot->getDriver()|get_class == "Tygh\Addons\Recaptcha\RecaptchaDriver"}
    <script>
        (function (_, $) {
            _.tr({
                error_validator_recaptcha: '{__("error_validator_recaptcha")|escape:"javascript"}',
                "recaptcha.recaptcha_cookie_title": '{__("recaptcha.recaptcha_cookie_title", ['skip_live_editor' => true])|escape:"javascript"}',
                "recaptcha.recaptcha_cookie_description": '{__("recaptcha.recaptcha_cookie_description", ['skip_live_editor' => true])|escape:"javascript"}',
            });

            $.extend(_, {
                recaptcha_settings: {
                    site_key: '{$addons.recaptcha.recaptcha_site_key|escape:javascript nofilter}',
                    theme: '{$addons.recaptcha.recaptcha_theme|escape:javascript nofilter}',
                    type: '{$addons.recaptcha.recaptcha_type|escape:javascript nofilter}',
                    size: '{$addons.recaptcha.recaptcha_size|escape:javascript nofilter}'
                },
                google_recaptcha_v3_site_key: '{$addons.recaptcha.recaptcha_v3_site_key|escape:javascript nofilter}',
                google_recaptcha_v2_token_param: '{"\Tygh\Addons\Recaptcha\RecaptchaDriver::RECAPTCHA_TOKEN_PARAM_NAME"|constant|escape:javascript nofilter}',
                google_recaptcha_v3_token_param: '{"\Tygh\Addons\Recaptcha\RecaptchaDriver::RECAPTCHA_V3_TOKEN_PARAM_NAME"|constant|escape:javascript nofilter}'
            });
        }(Tygh, Tygh.$));
    </script>
    {script src="js/addons/recaptcha/recaptcha.js" cookie-name="recaptcha"}
{/if}
