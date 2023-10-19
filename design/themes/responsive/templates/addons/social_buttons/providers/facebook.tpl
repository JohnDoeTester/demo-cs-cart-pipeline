{if $addons.social_buttons.facebook_enable == "Y" && $provider_settings.facebook.data}
{hook name="social_buttons:facebook"}
{$facebook_lang = $addons.social_buttons.facebook_lang}
{$facebook_app_id = $addons.social_buttons.facebook_app_id}

<div id="fb-root"></div>

<div class="fb-like" {$provider_settings.facebook.data nofilter}></div>
<div hidden
    data-ca-social-buttons="facebook"
    data-ca-social-buttons-src="//connect.facebook.net/{$facebook_lang}/all.js#xfbml=1&appId={$facebook_app_id}"></div>
{script src="js/addons/social_buttons/providers/facebook.js" class="cm-ajax-force" cookie-name="facebook"}
{/hook}
{/if}
