<div class="ty-recover-password">
    <form name="recoverfrm"
          action="{""|fn_url}"
          method="post"
    >
        {if $action == "request"}
            <div class="ty-control-group">
                <label class="ty-login__filed-label ty-control-group__label cm-trim cm-required"
                       for="login_id"
                >{__("email")}</label>
                <input type="text"
                       id="login_id"
                       name="user_email"
                       size="30"
                       value=""
                       class="ty-login__input cm-focus"
                />
            </div>
            {include file="common/image_verification.tpl" option="login" align="left"}
            <div class="buttons-container login-recovery">
                {include file="buttons/reset_password.tpl"
                         but_name="dispatch[auth.recover_password]"
                }
            </div>
        {elseif $action == "recover"}
            <input type="hidden"
                   name="ekey"
                   value="{$ekey}"
            />
            <div class="ty-control-group">
                <p>{__("press_continue_to_recover_password") nofilter}</p>
            </div>
            <div class="buttons-container login-recovery">
                {include file="buttons/button.tpl"
                         but_text=__("continue")
                         but_meta="ty-btn__secondary"
                         but_name="dispatch[auth.recover_password]"
                }
            </div>
        {/if}
    </form>
</div>
{capture name="mainbox_title"}{__("recover_password")}{/capture}