{if $show}
    <a id="license_errors"
       class="cm-dialog-opener cm-dialog-auto-size hidden cm-dialog-non-closable"
       data-ca-target-id="license_errors_dialog"
    ></a>

    <div class="hidden trial-expired-dialog license-errors-dialog"
         title="{__("licensing.license_error_license_is_`$license_errors.status|lower`.title", ["[product]" => $smarty.const.PRODUCT_NAME]) nofilter}"
         id="license_errors_dialog"
    >
        <form name="license_errors_form"
              id="license_errors_dialog_form"
              action="{""|fn_url}"
              method="post"
        >
            <input type="hidden"
                   name="redirect_url"
                   value="{$config.current_url}"
            />
            <input type="hidden"
                   name="store_mode"
                   value="full"
            />
            <div class="license-errors trial-expired">
                <p>{__("licensing.license_error_license_is_`$license_errors.status|lower`.text", [
                    "[product]" => $smarty.const.PRODUCT_NAME,
                    "[helpdesk_url]" => $config.resources.helpdesk_url
                ]) nofilter}</p>
                <div class="license type-error item">
                    <input type="text"
                           name="license_number"
                           class="type-error"
                           value="{$store_mode_license}"
                           placeholder="{__("please_enter_license_here")}"
                    />
                    <input name="dispatch[settings.change_store_mode]"
                           type="submit"
                           value="{__("activate")}"
                           class="btn btn-primary"
                    />
                </div>
            </div>
        </form>
    </div>

    <script>
        Tygh.$(document).ready(function () {
            Tygh.$('#license_errors').trigger('click');
        });

        Tygh.$(window).load(function () {
            Tygh.$('#license_errors_dialog_form').off('submit');
        });
    </script>
{/if}
