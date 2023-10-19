{if !$auth.user_id
    && $settings.Checkout.disable_anonymous_checkout == "YesNo::YES"|enum
}
    {$but_meta = $but_meta|default:"ty-btn__primary"}
    {$return_url = $but_href|default:("checkout.checkout"|fn_url)}

    <a
        class="cm-dialog-opener cm-dialog-auto-size ty-btn {$but_meta}"
        href="{"checkout.login_form?return_url=`$return_url|urlencode`"|fn_url}"
        data-ca-dialog-title="{__("sign_in")}"
        data-ca-target-id="checkout_login_form"
        rel="nofollow">
        {$but_text|default:__("proceed_to_checkout")}
    </a>
{else}
    {include
        file="buttons/button.tpl"
        but_text=$but_text|default:__("proceed_to_checkout")
        but_onclick=$but_onclick
        but_href=$but_href|default:"checkout.checkout"
        but_target=$but_target
        but_role=$but_action|default:"action"
        but_meta=$but_meta|default:"ty-btn__primary"
    }
{/if}
