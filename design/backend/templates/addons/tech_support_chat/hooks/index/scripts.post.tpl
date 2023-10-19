{if $auth.user_id
    && $auth.user_type === "UserTypes::ADMIN"|enum
    && $auth.is_root === "YesNo::YES"|enum
    && $smarty.session.tech_support_chat_widget_id
}
    <script>
        var __REPLAIN_ = '{$smarty.session.tech_support_chat_widget_id|escape:javascript}';
        {literal}
        (function (u) {
            var s = document.createElement('script');
            s.type = 'text/javascript';
            s.async = true;
            s.src = u;
            var x = document.getElementsByTagName('script')[0];
            x.parentNode.insertBefore(s, x);
        })('https://widget.replain.cc/dist/client.js');
        {/literal}
    </script>
{/if}
