{if $addons.social_buttons.vkontakte_enable == "Y" && $provider_settings.vkontakte.data && $addons.social_buttons.vkontakte_appid}
{hook name="social_buttons:vkontakte"}
<div id="vk_like"></div>

{$vkontakte_settings_prepared = "}, '"|explode:$provider_settings.vkontakte.data}
{$vkontakte_settings = [
    "`$vkontakte_settings_prepared[0]`}",
    $vkontakte_settings_prepared[1]|substr:0:-1
]}

<div hidden
    data-ca-social-buttons="vkontakte"
    data-ca-social-buttons-src="//vk.com/js/api/openapi.js"
    data-ca-social-buttons-api-id="{$addons.social_buttons.vkontakte_appid}"
    data-ca-social-buttons-settings="{$vkontakte_settings[0] nofilter}"
    data-ca-social-buttons-hash="{$vkontakte_settings[1]}"></div>
{script src="js/addons/social_buttons/providers/vkontakte.js" class="cm-ajax-force" cookie-name="vkontakte"}
{/hook}
{/if}
