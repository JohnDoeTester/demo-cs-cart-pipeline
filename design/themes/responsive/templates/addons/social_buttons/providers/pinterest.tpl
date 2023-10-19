{if $addons.social_buttons.pinterest_enable == "Y" && $provider_settings.pinterest.data}
{hook name="social_buttons:pinterest"}
<span class="pinterest__wrapper">
    <a href="//pinterest.com/pin/create/button/?url={$provider_settings.pinterest.data.url nofilter}&amp;media={$provider_settings.pinterest.data.media nofilter}&amp;description={$provider_settings.pinterest.data.description nofilter}" {$provider_settings.pinterest.data.params nofilter}><img src="//assets.pinterest.com/images/pidgets/pinit_fg_en_rect_red_{$addons.social_buttons.pinterest_size}.png" alt="Pinterest"></a>
</span>
<div hidden
    data-ca-social-buttons="pinterest"
    data-ca-social-buttons-src="//assets.pinterest.com/js/pinit.js"></div>
{script src="js/addons/social_buttons/providers/pinterest.js" class="cm-ajax-force" cookie-name="pinterest"}
{/hook}
{/if}
