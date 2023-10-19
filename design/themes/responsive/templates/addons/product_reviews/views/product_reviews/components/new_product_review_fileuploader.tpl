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
            max_images_upload: '{$max_images_upload}',

            post_max_size_mbytes: '{$post_max_size}',
            files_upload_max_size_mbytes: '{$upload_max_filesize}'
        });

        _.tr({
            file_is_too_large: '{__("file_is_too_large")|escape:"javascript"}',
            files_are_too_large: '{__("files_are_too_large")|escape:"javascript"}'
        });
    }(Tygh, Tygh.$));
</script>

{script src="js/tygh/fileuploader_scripts.js"}
{script src="js/tygh/node_cloning.js"}

<div class="ty-nowrap" id="file_uploader_{$id_var_name}">
    <div class="ty-fileuploader__file-section" id="message_{$id_var_name}" title="">
        <p class="cm-fu-file hidden">
            {include_ext file="common/icon.tpl"
                class="ty-icon-cancel-circle ty-fileuploader__icon"
                id="clean_selection_`$id_var_name`"
                title=__("remove_this_item")
                data=[
                    "onclick" => "Tygh.fileuploader.clean_selection(this.id); {if $multiupload !== 'Y'}Tygh.fileuploader.toggle_links(this.id, 'show');{/if} Tygh.fileuploader.check_required_field('{$id_var_name}', '{$label_id}');"
                ]
            }
            <span class="ty-fileuploader__filename ty-filename-link upload-filename"></span>
            {if $location == 'cart'}
                <br />
                {include file="buttons/update_cart.tpl"
                    but_id="button_cart_save_file"
                    but_name="dispatch[checkout.update]"
                    but_meta="hidden hidden-phone hidden-tablet"
                    but_text=__("save")
                }
            {/if}
        </p>
    </div>

    {strip}
    <div class="ty-fileuploader__file-link {if $multiupload != "Y" && $images}hidden{/if}" id="link_container_{$id_var_name}">
        <input type="hidden" name="file_{$var_name}" value="{if $image_name}{$image_name}{/if}" id="file_{$id_var_name}" class="cm-fileuploader-field" {if $disabled_param}disabled{/if}/>
        <input type="hidden" name="type_{$var_name}" value="{if $image_name}local{/if}" id="type_{$id_var_name}" class="cm-fileuploader-field" {if $disabled_param}disabled{/if}/>
        <div class="ty-fileuploader__file-local upload-file-local ty-fileuploader__drop-zone ty-fileuploader__drop-zone--visible" {""}
            data-ca-product-review="fileuploaderDropZone"
        >
            <div class="ty-fileuploader__drop-zone-description">
                {include_ext file="common/icon.tpl" class="ty-icon-image"}
                <div class="ty-fileuploader__drop-zone-text">
                    {__("product_reviews.uploader_drop_zone_description")}
                    </br>
                    {__("or")}
                </div>
            </div>

            <input type="file" class="ty-fileuploader__file-input" name="file_{$var_name}" id="local_{$id_var_name}" onchange="Tygh.fileuploader.show_loader(this.id); {if $multiupload == "Y"}Tygh.fileuploader.check_image(this.id);{else}Tygh.fileuploader.toggle_links(this.id, 'hide');{/if} Tygh.fileuploader.check_required_field('{$id_var_name}', '{$label_id}');{if $location == 'cart'}$('#button_cart_save_file').click();{/if}" data-ca-empty-file="" onclick="Tygh.$(this).removeAttr('data-ca-empty-file');">

            <div class="ty-fileuploader__drop-zone-buttons ty-fileuploader__drop-zone-buttons--visible" {""}
                data-ca-product-review="fileuploaderDropZoneButtons"
            >
                <a data-ca-multi="Y" {if !$images}class="ty-fileuploader__a-another hidden"{/if}>{$upload_another_file_text|default:__("upload_another_file")}</a>
                <a data-ca-target-id="local_{$id_var_name}" data-ca-multi="N" class="ty-fileuploader__a{if $images} hidden{/if}">{$upload_file_text|default:__("upload_file")}</a>
            </div>

            <div class="ty-fileuploader__drop-zone-description">
                {__("product_reviews.uploader_drop_zone_info", ['[max_size]' => $upload_max_filesize])}
            </div>

            {if $allow_url_uploading}
                &nbsp;{__("or")}&nbsp;
                <a onclick="Tygh.fileuploader.show_loader(this.id); {if $multiupload == "Y"}Tygh.fileuploader.check_image(this.id);{else}Tygh.fileuploader.toggle_links(this.id, 'hide');{/if} Tygh.fileuploader.check_required_field('{$id_var_name}', '{$label_id}');" id="url_{$id_var_name}">{__("specify_url")}</a>
            {/if}
        </div>
        {if $hidden_name}
            <input type="hidden" name="{$hidden_name}" id="hidden_input_{$id_var_name}" value="{$hidden_value}" class="cm-skip-avail-switch">
        {/if}
    </div>
    {/strip}
</div>
