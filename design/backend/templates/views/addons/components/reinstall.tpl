<div id="addon_upload_container">
    <form action="{""|fn_url}"
          method="post"
          name="addon_upload_form"
          class="form-horizontal cm-ajax"
          enctype="multipart/form-data"
    >
        <input type="hidden" name="result_ids" value="addon_upload_container"/>
        <input type="hidden" name="addon_extract_path" value="{$addon_extract_path}"/>
        <input type="hidden" name="addon_name" value="{$addon_name}"/>
        <input type="hidden" name="return_url" value="{$return_url|fn_url}"/>

        <p>{__("addon_reinstall.intro")}</p>
        <p>{__("addon_reinstall.safe_way") nofilter}</p>
        <p>{__("addon_reinstall.dangerous_way") nofilter}</p>
        <p>
            <label class="checkbox muted">
                <input type="checkbox"
                       class="cm-combination"
                       id="sw_continue_without_addon_removal"
                />
                {__("addon_reinstall.dangerous_way.confirm")}
            </label>
        </p>
        <div class="buttons-container">
            {include file="buttons/button.tpl"
                but_id="continue_without_addon_removal"
                but_text=__("addon_reinstall.dangerous_way.action")
                but_role="submit"
                but_meta="hidden"
                but_name="dispatch[addons.recheck]"
                }
            {include file="buttons/button.tpl"
                but_id="remove_addon_and_continue"
                but_text=__("addon_reinstall.safe_way.action")
                but_role="button_main"
                but_name="dispatch[addons.recheck..uninstall]"
            }
        </div>
    </form>
    <!--addon_upload_container--></div>
