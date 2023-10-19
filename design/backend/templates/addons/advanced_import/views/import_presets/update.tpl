{capture name="mainbox"}
    {capture name="tabsbox"}

        {$id = $preset.preset_id|default:0}
        {$disable_picker = $disable_picker|default: false}

        <input type="hidden" name="step_map_fields" id="step_map_fields" value="{$step_map_fields|default: false}"/>

        <form action="{""|fn_url}"
              method="post"
              name="import_preset_update_form"
              id="import_preset_update_form"
              enctype="multipart/form-data"
              class="form-horizontal form-edit{if $start_import} cm-ajax cm-comet{/if} import-preset-edit"
              data-ca-advanced-import-element="editor"
              data-ca-advanced-import-preset-id="{$id}"
              data-ca-advanced-import-preset-object-type="{$preset.object_type}"
              data-ca-advanced-import-preset-name="{$preset.preset}"
        >

            <input type="hidden" name="preset_id" value="{$id}"/>
            <input type="hidden" name="result_ids" value="content_{$id}"/>
            <input type="hidden" name="object_type" value="{$preset.object_type}"/>
            {if $start_import}
                <input type="hidden" name="return_url" value="{"import_presets.update&preset_id=`$id`"}"/>
            {/if}

            <div id="content_general">

                {* adds a CRON task message *}
                {if !empty($id) && (!$runtime.company_id || $runtime.simple_ultimate)}
                    <p>{__("advanced_import.run_import_via_cron_message")}</p>
                    {if $preset.company_id}
                        <pre><code>{"php /path/to/cart/"|fn_get_console_command:$config.admin_index:[
                            "dispatch"  => "advanced_import.import.import",
                            "preset_id" => {$id},
                            "switch_company_id" => {$preset.company_id},
                            "p"
                        ]}</code></pre>
                    {else}
                        <pre><code>{"php /path/to/cart/"|fn_get_console_command:$config.admin_index:[
                            "dispatch"  => "advanced_import.import.import",
                            "preset_id" => {$id},
                            "p"
                        ]}</code></pre>
                    {/if}
                {/if}

                <div id="information" class="in collapse">

                    <div class="control-group {if $preset.file}cm-skip-validation{/if}">
                        <input type="hidden"
                               data-ca-advanced-import-element="file_type"
                               name="file_type"
                               value="{$preset.file_type|default:("Addons\\AdvancedImport\\PresetFileTypes::LOCAL"|enum)}"
                        />
                        <input type="hidden"
                               name="file"
                               data-ca-advanced-import-element="file"
                               value="{$preset.file|default:""}"
                        />

                        {$var_name = "upload[{$id}]"}
                        {$id_var_name = "upload_{$id}"}

                        <label for="type_{$id_var_name}" class="control-label cm-required">{__("file")}:</label>
                        <div class="controls import-preset__fileuploader">
                            {include file="addons/advanced_import/views/import_presets/components/fileuploader.tpl"
                                var_name=$var_name
                                id_var_name=$id_var_name
                                allowed_ext=$allowed_ext|default: ["csv", "xml"]
                                hide_server=true
                                upload_file_text=__("upload_file")
                                upload_url_file_text=__("advanced_import.link_to_file")
                            }
                        </div>
                    </div>

                    <div class="control-group">
                        <input type="hidden" id="archive_path" name="archive_path" value="{$archive_path|default:""}"/>
                        <label class="control-label">{__("advanced_import.archive_with_images")}:</label>
                        <div class="controls">
                            {include file="addons/advanced_import/views/import_presets/components/archiveuploader.tpl"
                                archives=$archive_images
                            }
                        </div>
                    </div>

                    <div class="control-group {$preset.options.target_node.control_group_meta}" data-ca-default-hidden="{if $preset.file}false{else}true{/if}">
                        <label for="target_node" class="control-label">
                            {__($preset.options.target_node.title)}
                        </label>
                        <div class="controls">
                            <input class="input-large"
                                   type="text"
                                   name="options[target_node]"
                                   id="target_node"
                                   size="55"
                                   value="{$preset.options.target_node.selected_value|default:$preset.options.target_node.default_value}"
                            />
                            {if $preset.options.target_node.description}
                                <p class="muted description">{__($preset.options.target_node.description)}</p>
                            {/if}
                        </div>
                    </div>

                    <div class="control-group">
                        <label for="elm_preset" class="control-label cm-required">{__("name")}:</label>
                        <div class="controls">
                            <input class="input-large"
                                   type="text"
                                   name="preset"
                                   id="elm_preset"
                                   size="55"
                                   value="{$preset.preset}"
                            />
                        </div>
                    </div>

                    {hook name="import_presets:options"}{/hook}
                    {if $is_mve}
                        {include file="views/companies/components/company_field.tpl"
                            name="company_id"
                            id="elm_company_id"
                            selected=$preset.company_id
                            disable_company_picker=$disable_picker
                            js_action="$.ceAdvancedImport('changeCompanyId');"
                            required=false
                            zero_company_id_name_lang_var="advanced_import.common_preset"
                        }
                    {else}
                        {include file="views/companies/components/company_field.tpl"
                            name="company_id"
                            id="elm_company_id"
                            selected=$preset.company_id
                            js_action="$.ceAdvancedImport('changeCompanyId');"
                            required=true
                            zero_company_id_name_lang_var="none"
                        }
                    {/if}
                </div>

                {include file="common/subheader.tpl"
                    title=__("advanced_import.additional_settings")
                    target="#import_file"
                    meta="collapsed"
                }

                <div id="import_file" class="{if $view_only}cm-hide-inputs{/if} out collapse">

                    <div class="control-group">
                        <label class="control-label">{__("csv_delimiter")}:</label>
                        <div class="controls" data-ca-advanced-import-element="delimiter_container">
                            {$auto_delimiter = "Addons\AdvancedImport\CsvDelimiters::AUTO"|enum}
                            {include file="views/exim/components/csv_delimiters.tpl"
                                name="options[delimiter]"
                                value="{$preset.options.delimiter|default:$auto_delimiter}"
                                allow_auto_detect=true
                            }
                        </div>
                    </div>

                    <div class="control-group cm-no-hide-input">
                        {$images_path = $preset.options.images_path}
                        <label for="images_path" class="control-label">{__("images_directory")}:</label>
                        <div class="controls">
                            <div class="input-prepend">
                                <span class="add-on" id="advanced_import_images_path_prefix" data-companies-image-directories="{$images_path.companies_image_directories|to_json}">
                                    {$images_path.input_prefix}
                                </span>

                                <input id="images_path"
                                       class="input-large prefixed"
                                       type="text"
                                       name="options[images_path]"
                                       value="{$images_path.display_value}"
                                />
                            </div>

                            <div id="images_path_dialog" class="hidden"></div>
                            <p class="muted description">{__("advanced_import.text_popup_file_editor_notice_full_link", ["[target]" => "images_path", "[link_text]" => {__("file_editor")}])}</p>
                            <p class="muted description">{__($images_path.description)}</p>
                        </div>
                    </div>

                    {include file="addons/advanced_import/views/import_presets/components/options.tpl"
                        options=$preset.options|default:[]
                        field_name_prefix="options"
                        display=true
                        tab="general"
                    }

                    {capture name="buttons"}
                        {capture name="tools_list"}
                            {hook name="advanced_import:update_tools_list"}
                            {/hook}
                        {/capture}
                        {dropdown content=$smarty.capture.tools_list}

                        {if $start_import}
                            {include file="buttons/button.tpl"
                                but_text=__("import")
                                but_role="action"
                                but_id="advanced_import_start_import"
                                but_meta="cm-submit hidden cm-advanced-import-start-import"
                                but_target_form="import_preset_update_form"
                                but_name="dispatch[advanced_import.import]"
                            }
                        {/if}
                        {include file="buttons/button.tpl"
                            but_text="{__("import")}"
                            but_role="action"
                            but_id="advanced_import_save_and_import"
                            but_name="dispatch[import_presets.update.import]"
                            but_target_form="import_preset_update_form"
                            but_meta="cm-submit btn-primary{if !$id || !$preset.file} hidden{/if}"
                        }
                        {if $view_only}
                            {include file="buttons/button.tpl"
                                but_text=__("advanced_import.save_selected_file")
                                but_role="action"
                                but_id="advanced_import_upload"
                                but_meta="btn-primary cm-submit cm-ajax cm-post hidden"
                                but_target_form="import_preset_update_form"
                                but_name="dispatch[import_presets.upload.detailed]"
                            }
                            {include file="buttons/button.tpl"
                                but_text="{__("clone")}"
                                but_role="action"
                                but_name="dispatch[advanced_import.clone]"
                                but_target_form="import_preset_update_form"
                                but_meta="cm-submit{if !$id} btn-primary{/if}"
                            }
                        {else}
                            {include file="buttons/button.tpl"
                                but_text="{if $id}{__("save")}{else}{__("advanced_import.step_map_fields")}{/if}"
                                but_role="action"
                                but_name="dispatch[import_presets.update]"
                                but_target_form="import_preset_update_form"
                                but_meta="cm-submit{if !$id} btn-primary{/if}"
                            }
                        {/if}
                    {/capture}
                </div>

            <!--content_general--></div>

            <div class="hidden" id="content_fields">
            <!--content_fields--></div>

            <div class="hidden {if $view_only}cm-hide-inputs{/if}" id="content_options">

                {include file="common/subheader.tpl"
                    title=__("advanced_import.general_settings")
                    target="#settings_general"
                }

                <div id="settings_general" class="out">
                    {include file="addons/advanced_import/views/import_presets/components/options.tpl"
                        options=$preset.options|default:[]
                        field_name_prefix="options"
                        display=true
                        tab="settings"
                        section="general"
                    }
                </div>

                {include file="common/subheader.tpl"
                    title=__("advanced_import.additional_settings")
                    target="#settings_additional"
                    meta="collapsed"
                }

                <div id="settings_additional" class="out collapse">
                    {include file="addons/advanced_import/views/import_presets/components/options.tpl"
                        options=$preset.options|default:[]
                        field_name_prefix="options"
                        display=true
                        tab="settings"
                        section="additional"
                    }
                </div>
            <!--content_options--></div>

        </form>
    {/capture}

    {include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox active_tab="general"}
{/capture}

{include file="common/mainbox.tpl"
    title=($preset.preset_id) ? $preset.preset : __("advanced_import.new_preset")
    content=$smarty.capture.mainbox
    buttons=$smarty.capture.buttons
}
