{$use_for_settings_variants = fn_settings_variants_image_verification_use_for()}
{$settings = fn_recaptcha_get_use_for_settings($selected_storefront_id)}

{$is_update_for_all_button_displayed = false}
{$is_update_for_all_available = !$selected_storefront_id
    && $app['storefront.repository']->getCount(['cache' => true]) > 1
}

{foreach $use_for_settings_variants as $variant => $variant_description}
    <div class="control-group setting-wide">
        <label class="control-label" for="addon_option_recaptcha_use_for_{$variant}">{$variant_description}:</label>
        <div class="controls">
            <label class="radio">
                <input
                    type="radio"
                    name="recaptcha_use_for[{$variant}]"
                    value="{"Addons\\Recaptcha\\RecaptchaTypes::RECAPTCHA_TYPE_V2"|enum}"
                    {if $settings.$variant == "Addons\\Recaptcha\\RecaptchaTypes::RECAPTCHA_TYPE_V2"|enum}checked="checked"{/if}
                    id="addon_option_recaptcha_use_for_{$variant}"
                    data-ca-recaptcha-settings="use_for_{$variant}"
                    {if $is_update_for_all_available}
                        disabled="disabled"
                    {/if}
                >{__("recaptcha.recaptcha_v2")}
            </label>
            <label class="radio">
                <input
                    type="radio"
                    name="recaptcha_use_for[{$variant}]"
                    value="{"Addons\\Recaptcha\\RecaptchaTypes::RECAPTCHA_TYPE_V3"|enum}"
                    {if $settings.$variant == "Addons\\Recaptcha\\RecaptchaTypes::RECAPTCHA_TYPE_V3"|enum}checked="checked"{/if}
                    id="addon_option_recaptcha_use_for_{$variant}"
                    data-ca-recaptcha-settings="use_for_{$variant}"
                    {if $is_update_for_all_available}
                        disabled="disabled"
                    {/if}
                >{__("recaptcha.recaptcha_v3")}
            </label>
            <label class="radio">
                <input
                    type="radio"
                    name="recaptcha_use_for[{$variant}]"
                    value=""
                    {if $settings.$variant != "Addons\\Recaptcha\\RecaptchaTypes::RECAPTCHA_TYPE_V2"|enum && $settings.$variant != "Addons\\Recaptcha\\RecaptchaTypes::RECAPTCHA_TYPE_V3"|enum}
                        checked="checked"
                    {/if}
                    id="addon_option_recaptcha_use_for_{$variant}"
                    data-ca-recaptcha-settings="use_for_{$variant}"
                    {if $is_update_for_all_available}
                        disabled="disabled"
                    {/if}
                >{__("recaptcha.none")}
            </label>
            {if $is_update_for_all_available && !$is_update_for_all_displayed}
                {$is_update_for_all_displayed = true}
                <div class="right">
                    {include file="buttons/update_for_all.tpl"
                        display=true
                        name="update_all_vendors[use_for]"
                        object_id="update_for_all_use_for"
                        component="recaptcha.use_for"
                    }
                </div>
                <script>
                    (function(_, $) {
                        $(_.doc).on('click', '[data-ca-update-for-all="recaptcha.use_for"]', function (e) {
                            var $controls = $('[name*="recaptcha_use_for"]');
                            var currentState = $controls.prop('disabled');
                            $controls.prop('disabled', currentState ? null : 'disabled');
                        });
                    })(Tygh, Tygh.$);
                </script>
            {/if}
        </div>
    </div>
{/foreach}

{script src="js/addons/recaptcha/backend/settings.js"}
