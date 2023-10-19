{if $addons.social_buttons.yandex_enable == "Y" && $provider_settings.yandex.data}
{hook name="social_buttons:yandex"}
{$provider_settings.yandex.data nofilter}
<div hidden data-ca-social-buttons="yandex" data-ca-social-buttons-src="//yastatic.net/share2/share.js"></div>
{script src="js/addons/social_buttons/providers/yandex.js" class="cm-ajax-force" cookie-name="yandex"}
{/hook}
{/if}
