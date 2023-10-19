{$post_max_size = $server_env->getIniVar("post_max_size")}
{$upload_max_filesize = $server_env->getIniVar("upload_max_filesize")}

{if $max_upload_filesize}
    {if $post_max_size > $max_upload_filesize}
        {$post_max_size = $max_upload_filesize}
    {/if}
    {if $upload_max_filesize > $max_upload_filesize}
        {$upload_max_filesize = $max_upload_filesize}
    {/if}
{/if}

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

{$label_id="archive"}
{$prefix="archives_"}
{$var_name="archive_images[]"}
{$id_var_name="`$prefix`{$var_name|md5}"}

{foreach from=$archives key="archive_id" item="archive"}
    <input type="hidden"
           id="{$id_var_name}_{$archive.file}_type"
           name="uploaded_archives_type[{$archive_id}]"
           data-ca-advanced-import-element="temp_archive_type"
           value="local"
    />
    <input type="hidden"
           id="{$id_var_name}_{$archive.file}_file"
           name="uploaded_archives[{$archive_id}]"
           data-ca-advanced-import-element="temp_archive_images"
           value="{$archive.name|default:""}"
    />
{/foreach}

<div class="fileuploader cm-fileuploader cm-field-container" {if $disabled_param}hidden disabled{/if}>
    <input type="hidden" id="{$label_id}" value="{if $archives}{$id_var_name}{/if}" />

    {foreach from=$archives key="archive_id" item="archive"}
        <div class="upload-file-section cm-uploaded-image" id="message_{$id_var_name}_{$archive.file}" title="">
            <p class="cm-fu-file">
                {$download_link = "import_presets.get_archive?preset_id=`$id`&file=`$archive.name`&file_type=`$archive.type`"}

                {if !($po.required === "YesNo::YES"|enum && $archives|count == 1)}{include_ext file="common/icon.tpl"
                    class="icon-remove-sign cm-tooltip hand"
                    id="clean_selection_`$id_var_name`_`$archive.file`"
                    title=__("remove_this_item")
                    data=[
                    "onclick" => "Tygh.fileuploader.clean_selection(this.id); Tygh.fileuploader.check_required_field('`$id_var_name`', '`$label_id`');"
                    ]
                    icon_text=""
                }&nbsp;{/if}<span class="upload-filename">{if $download_link}<a href="{$download_link|fn_url}">{/if}{$archive.name}{if $download_link}</a>{/if}</span>
            </p>
        </div>
    {/foreach}

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
                    icon_text=""
                }&nbsp;<span class="upload-filename"></span>
            </p>
        </div>

        {strip}
            <input type="hidden" name="file_{$var_name}" value="{if $image_name}{$image_name}{/if}" id="file_{$id_var_name}" class="cm-fileuploader-field"/>
            <input type="hidden" name="type_{$var_name}" value="{if $image_name}local{/if}" id="type_{$id_var_name}" class="cm-fileuploader-field"/>
            <div class="btn-group" id="link_container_{$id_var_name}">
                <div class="upload-file-local">
                    <a class="btn"><span data-ca-multi="Y" {if !$archives}class="hidden"{/if}>{$upload_another_file_text|default:__("upload_another_file")}</span><span data-ca-multi="N" {if $archives}class="hidden"{/if}>{__("upload_file")}</span></a>
                    <div class="image-selector">
                        <label for="">
                            <input type="file" name="file_{$var_name}" id="local_{$id_var_name}" onchange="Tygh.fileuploader.show_loader(this.id); Tygh.fileuploader.check_image(this.id); Tygh.fileuploader.check_required_field('{$id_var_name}', '{$label_id}');" class="file" data-ca-empty-file="" onclick="Tygh.$(this).removeAttr('data-ca-empty-file');">
                        </label>
                    </div>
                </div>
                <a class="btn" onclick="Tygh.fileuploader.show_loader(this.id);" id="url_{$id_var_name}">{__("advanced_import.link_to_file")}</a>
            </div>
        {/strip}
    </div>

    <p class="mute micro-note">
        {__("text_allowed_to_upload_file_extension", ["[ext]" => 'zip, tgz, gz'])}
    </p>
</div>
