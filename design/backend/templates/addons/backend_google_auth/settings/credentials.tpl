<div class="well">
    {__("backend_google_auth.settings.create_new_application")}
    <a href="{$config.resources.docs_url}user_guide/addons/google_backend_signin/settings.html" target="_blank">
        {__("backend_google_auth.settings.learn_more_about")}
    </a>
</div>

{include file="common/widget_copy.tpl"
    widget_copy_text=__("backend_google_auth.settings.authorized_redirect_uris")
    widget_copy_code_text="backend_google_auth.callback?hauth.done={$smarty.const.BACKEND_GOOGLE_AUTH_PROVIDER}"|fn_url:"A"
}

<p>
    {__("backend_google_auth.settings.provide_your_credentials")}:
</p>
