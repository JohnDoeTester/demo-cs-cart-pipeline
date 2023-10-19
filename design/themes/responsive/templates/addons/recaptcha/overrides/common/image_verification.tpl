{if $option|fn_needs_image_verification}
    {$id = "recaptcha_"|uniqid}
    {if $app.antibot->getDriver()|get_class == "Tygh\Addons\Recaptcha\RecaptchaDriver"}
        {$recaptcha_type = $option|fn_recaptcha_get_recaptcha_type_by_scenario}
        {if $recaptcha_type == "Addons\\Recaptcha\\RecaptchaTypes::RECAPTCHA_TYPE_V2"|enum}
            <div class="captcha ty-control-group">
                <label for="{$id}" class="cm-required cm-recaptcha ty-captcha__label">{__("image_verification_label")}</label>
                <div id="{$id}" class="cm-recaptcha"></div>
            </div>
        {elseif $recaptcha_type == "Addons\\Recaptcha\\RecaptchaTypes::RECAPTCHA_TYPE_V3"|enum}
            <input type="hidden" data-recaptcha-v3-action="{$option}" class="cm-recaptcha-v3" name={"\Tygh\Addons\Recaptcha\RecaptchaDriver::RECAPTCHA_V3_TOKEN_PARAM_NAME"|constant} id="g_recaptcha_v3_token_{$id}" />
        {/if}
    {else}
        <div class="native-captcha{if !$full_width} native-captcha--short{/if}">
            <label for="{$id}" class="cm-required ty-captcha__label">{__("image_verification_label")}</label>
            <div class="native-captcha__image-container">
                <img src="{$smarty.session.native_captcha.image}" class="native-captcha__image"/>
            </div>
            <input
                type="text"
                id="{$id}"
                class="input-text native-captcha__answer form-control"
                name="native_captcha_response"
                autocomplete="off"
                placeholder="{__("image_verification_label")}"
            >
        </div>
    {/if}
{/if}
