{script src="js/tygh/fileuploader_scripts.js"}

{$id_var_name="`$prefix`{$var_name|md5}"}

{if !$but_text}
    {$but_text = __("sw.select_image")}
{/if}

{strip}
    <div class="sw-fileuploader clearfix">
        <div class="upload-file-section" id="message_{$id_var_name}" title="">
            <p class="cm-fu-file hidden">
                {include_ext file="common/icon.tpl"
                    class="glyph-cancel"
                    id="clean_selection_`$id_var_name`"
                    title=__("remove_this_item")
                    data=[
                        "onclick" => "Tygh.fileuploader.clean_selection(this.id); Tygh.fileuploader.toggle_links(this.id, 'show');"
                    ]
                    icon_text="&nbsp;"
                }
                <span class="upload-filename filename-link"></span>
            </p>
        </div>

        <div id="link_container_{$id_var_name}">
            {if $but_type === "link"}
                <a class="ty-left fileinput-btn">
            {else}
                <span class="btn ty-left fileinput-btn">
            {/if}
                    <input type="file" name="file_{$var_name}" id="local_{$id_var_name}" onchange="Tygh.fileuploader.show_loader(this.id); Tygh.fileuploader.toggle_links(this.id, 'hide');" data-ca-empty-file="" onclick="Tygh.$(this).removeAttr('data-ca-empty-file');">{include_ext file="common/icon.tpl" class="glyph-image"}{$but_text}
            {if $but_type === "link"}
                </a>
            {else}
                </span>
            {/if}
            <input type="hidden" name="file_{$var_name}" value="" id="file_{$id_var_name}">
            <input type="hidden" name="type_{$var_name}" value="" id="type_{$id_var_name}">

            {if $required === "YesNo::YES"|enum}
                <label for="file_{$id_var_name}" class="cm-required hidden"></label>
            {/if}

        </div>
    </div>
{/strip}

