<div title="{__("sign_in")}">
    <div class="ty-login-popup">
        {if $title}
            <h3>{$title}</h3>
        {/if}
        {include file="views/auth/login_form.tpl" style="popup" id="auth_login"}
    </div>
</div>
