{if $addons.social_buttons.twitter_enable == "Y" && $provider_settings.twitter.data}
{hook name="social_buttons:twitter"}
<a href="https://twitter.com/share" class="twitter-share-button" {$provider_settings.twitter.data nofilter}>Tweet</a>
<div hidden
    data-ca-social-buttons="twitter"
    data-ca-social-buttons-src="//platform.twitter.com/widgets.js"></div>
{script src="js/addons/social_buttons/providers/twitter.js" class="cm-ajax-force" cookie-name="twitter"}
{/hook}
{/if}
