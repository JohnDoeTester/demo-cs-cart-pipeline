{$post_max_size = $server_env->getIniVar("post_max_size")}
{$upload_max_filesize = $server_env->getIniVar("upload_max_filesize")}

<script>
    (function(_, $) {
        $.extend(_, {
            post_max_size_bytes: '{$post_max_size|fn_return_bytes}',
            files_upload_max_size_bytes: '{$upload_max_filesize|fn_return_bytes}',

            post_max_size_mbytes: '{$post_max_size}',
            files_upload_max_size_mbytes: '{$upload_max_filesize}',
            allowed_file_path: '{fn_get_http_files_dir_path()}'
        });

        _.tr({
            file_is_too_large: '{__("file_is_too_large")|escape:"javascript"}',
            files_are_too_large: '{__("files_are_too_large")|escape:"javascript"}'
        });
    }(Tygh, Tygh.$));
</script>

{script src="js/tygh/fileuploader_scripts.js"}
{script src="js/tygh/node_cloning.js"}

<div class="fileuploader cm-field-container">
    <input type="hidden" id="{$label_id}" value="" />

    <div id="file_uploader_{$id_var_name}">
        <div class="upload-file-section" id="message_{$id_var_name}" title="">
            <p class="cm-fu-file hidden">
                {include_ext file="common/icon.tpl"
                    class="icon-remove-sign cm-tooltip hand"
                    id="clean_selection_`$id_var_name`"
                    title=__("remove_this_item")
                    data=[
                        "onclick" => "Tygh.fileuploader.clean_selection(this.id); Tygh.fileuploader.check_required_field('{$id_var_name}', '{$label_id}');"
                    ]
                }&nbsp;<span class="upload-filename"></span>
            </p>
            <p class="cm-fu-no-file">
                {if $preset.file}
                        <a href="{"import_presets.get_file?preset_id=`$preset.preset_id`&company_id=`$runtime.company_id`"|fn_url}">
                            {$preset.file}
                        </a>
                {else}
                    {__("text_select_file")}
                {/if}
            </p>
        </div>

        {strip}
            <input type="hidden" name="file_{$var_name}" value="" id="file_{$id_var_name}" />
            <input type="hidden" name="type_{$var_name}" value="" id="type_{$id_var_name}" />

            <div class="btn-group" id="link_container_{$id_var_name}">
                <div class="upload-file-local">
                    <a class="btn"><span data-ca-multi="N">{$upload_file_text|default:__("local")}</span></a>
                    <div class="image-selector">
                        <label for="">
                            {/strip}
                            <input type="file"
                                   name="file_{$var_name}"
                                   id="local_{$id_var_name}"
                                   onchange="Tygh.fileuploader.show_loader(this.id);Tygh.fileuploader.check_required_field('{$id_var_name}', '{$label_id}');"
                                   class="file"
                                   data-ca-empty-file=""
                                   onclick="Tygh.$(this).removeAttr('data-ca-empty-file');"
                                   accept=".{",."|implode:$allowed_ext}"
                            />
                            {strip}
                        </label>
                    </div>
                </div>
                {if !$hide_server}
                    <a class="btn" onclick="Tygh.fileuploader.show_loader(this.id);" id="server_{$id_var_name}">
                        {__("server")}
                    </a>
                {/if}
                <a class="btn" onclick="Tygh.fileuploader.show_loader(this.id);" id="url_{$id_var_name}">{$upload_url_file_text|default:__("url")}</a>
                {if $hidden_name}
                    <input type="hidden" name="{$hidden_name}" value="{$hidden_value}">
                {/if}
            </div>

            {if $allowed_ext}
                <p class="mute micro-note">
                    {__("text_allowed_to_upload_file_extension", ["[ext]" => ", "|implode:$allowed_ext]) nofilter}
                </p>
            {/if}

        {/strip}
    </div>

</div><!--fileuploader-->
