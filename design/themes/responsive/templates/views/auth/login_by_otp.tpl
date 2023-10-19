{$container_id = $container_id|default:"login_by_otp_container"}

<div id="{$container_id}">
    <form action="{""|fn_url}" method="post" class="cm-ajax cm-post" id="login_by_otp_form" name="login_by_otp_form">
        <input type="hidden" name="email" value="{$email}" />
        <input type="hidden" name="return_url" value="{$return_url}" />
        <input type="hidden" name="container_id" value="{$container_id}" />
        <input type="hidden" name="result_ids" value="{$container_id}" />
        <input type="hidden" name="dispatch" value="auth.login_by_otp" />

        <div class="ty-product-notification__body cm-notification-max-height">
            {__("auth.one_time_password.popup.message", ["[email]" => $email]) nofilter}
            <p class="ty-center">
                <label for="one_time_password_input" class="hidden cm-required">{__("code")}</label>
                <input type="text"
                    id="one_time_password_input"
                    placeholder="{__("auth.one_time_password.popup.input_placeholder")}"
                    name="password"
                    class="ty-input-text cm-input-text-auto-submit cm-focus"
                    autocomplete="off"
                    maxlength="{$smarty.const.USER_ONE_TIME_PASSWORD_LENGTH}"
                    autofocus
                />
            </p>
        </div>
        <div class="ty-product-notification__buttons buttons-container clearfix">
            <div class="ty-float-left">
                {include file="buttons/button.tpl"
                    but_text=__("auth.one_time_password.popup.resend_btn")
                    but_role="text"
                    but_href="auth.send_otp.resend"|fn_url
                    but_meta="cm-ajax cm-post cm-ajax-send-form"
                    but_target_form="#login_by_otp_resend_form"
                }
            </div>
            <div class="ty-right">
                {include file="buttons/button.tpl"
                    but_id="login_by_otp_sign_in_btn"
                    but_name="dispatch[auth.login_by_otp]"
                    but_text=__("auth.one_time_password.popup.sign_in_btn")
                    but_meta="ty-btn__secondary"
                    but_role="submit"
                }
            </div>
        </div>
    </form>
    <form action="{""|fn_url}" method="post" class="cm-ajax cm-post" id="login_by_otp_resend_form" name="login_by_otp_resend_form">
        <input type="hidden" name="email" value="{$email}" />
        <input type="hidden" name="return_url" value="{$return_url}" />
    </form>
<!--{$container_id}--></div>