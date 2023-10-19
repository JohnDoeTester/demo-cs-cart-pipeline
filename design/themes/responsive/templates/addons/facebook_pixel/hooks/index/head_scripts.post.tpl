{hook name="facebook_pixel:head_scripts"}
{/hook}

{if $addons.facebook_pixel.pixel_id}
<script {$script_attrs|render_tag_attrs nofilter}>
    !function(f,b,e,v,n,t,s)
            { if(f.fbq)return;n=f.fbq=function(){ n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.agent='plcscart';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '{$addons.facebook_pixel.pixel_id|escape:"javascript"}');
    {if $addons.facebook_pixel.track_all_page_views === "YesNo::YES"|enum}
        fbq('track', 'PageView');
    {/if}
    {if $addons.facebook_pixel.track_order_placed === "YesNo::YES"|enum && $fb_track_order_placed_event}
        {if $fb_order_total}
            fbq('track', 'Order Placed', {literal}{{/literal}currency: "{$primary_currency}", value: {$fb_order_total}{literal}}{/literal});
        {else}
            fbq('track', 'Order Placed');
        {/if}
    {/if}
</script>
<noscript>
    <img height="1" width="1" style="display:none"
         src="https://www.facebook.com/tr?id={$addons.facebook_pixel.pixel_id|escape:"url"}&ev=PageView&noscript=1";
    />
</noscript>
{/if}
