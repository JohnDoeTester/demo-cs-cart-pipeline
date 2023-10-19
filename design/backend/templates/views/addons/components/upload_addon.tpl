{$c_url = $config.current_url}
<div id="addon_upload_container" class="install-addon">
    <form action="{""|fn_url}" method="post" name="addon_upload_form" class="form-horizontal cm-ajax" enctype="multipart/form-data">
        <input type="hidden" name="result_ids" value="addon_upload_container" />
        <input type="hidden" name="return_url" value="{$c_url|fn_url}" />
        <div class="install-addon-wrapper">
            {include_ext file="common/icon.tpl"
                class="icon-puzzle-piece install-addon-banner"
                data=[
                    "width" => "151px",
                    "height" => "141px"
                ]
            }

            <p class="install-addon-text">{__("install_addon_text", ['[exts]' => implode(',', $config.allowed_pack_exts)]) nofilter}</p>
            {include file="common/fileuploader.tpl" var_name="addon_pack[0]"}

            <div class="marketplace">
                <p class="marketplace-link"> {__("marketplace_find_more", ["[href]" => $config.resources.marketplace_url]) nofilter} </p>
            </div>

        </div>

        <div class="buttons-container">
            {include file="buttons/save_cancel.tpl" but_name="dispatch[addons.upload]" cancel_action="close" but_text=__("upload_install")}

        </div>
    </form>
<!--addon_upload_container--></div>
