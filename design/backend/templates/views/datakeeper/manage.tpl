{script src="js/tygh/tabs.js"}

{$c_url=$config.current_url|fn_query_remove:"sort_by":"sort_order"}

{capture name="mainbox"}

    {include_ext file="common/icon.tpl" class="icon-`$search.sort_order_rev`" assign=c_icon}
    {include_ext file="common/icon.tpl" class="icon-dummy" assign=c_dummy}

    {notes title=__("current_database_size")}
        <p><span>{$database_size|number_format}</span> {__("bytes")}</p>
    {/notes}
    <div id="backup_management">
        <form action="{""|fn_url}" method="post" name="backups_form">
            {hook name="backups:manage"}
            {$result_ids = "backup_management,actions_panel"}
                <input type="hidden" name="result_ids" value="{$result_ids}"/>
            {if $backup_files}
                {capture name="datakeepers_table"}
                    <div class="table-responsive-wrapper longtap-selection">
                        <table class="table table-middle table--relative table-responsive">
                            <thead
                                data-ca-bulkedit-default-object="true"
                                data-ca-bulkedit-component="defaultObject"
                            >
                            <tr>
                                <th width="6%" class="mobile-hide">
                                    {include file="common/check_items.tpl" is_check_all_shown=true}

                                    <input type="checkbox"
                                        class="bulkedit-toggler hide"
                                        data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]" 
                                        data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                                    />
                                </th>
                                <th width="40%">
                                    <a class="cm-ajax"
                                    href="{"`$c_url`&sort_by=name&sort_order=`$search.sort_order_rev`"|fn_url}"
                                    data-ca-target-id="backup_management">{__("filename")}
                                    {if $search.sort_by === "name"}{$c_icon nofilter}{/if}
                                    </a>
                                </th>
                                <th width="15%">
                                    <a class="cm-ajax"
                                    href="{"`$c_url`&sort_by=mtime&sort_order=`$search.sort_order_rev`"|fn_url}"
                                    data-ca-target-id="backup_management">{__("date")}
                                    {if $search.sort_by === "mtime"}{$c_icon nofilter}{/if}
                                    </a>
                                </th>
                                <th width="15%">
                                    <a class="cm-ajax"
                                    href="{"`$c_url`&sort_by=size&sort_order=`$search.sort_order_rev`"|fn_url}"
                                    data-ca-target-id="backup_management">{__("filesize")}
                                    {if $search.sort_by === "size"}{$c_icon nofilter}{/if}
                                    </a>
                                </th>
                                <th width="10%">{__("type")}</th>
                                <th width="8%">&nbsp;</th>
                            </tr>
                            </thead>

                            {foreach $backup_files as $name => $file}
                                <tr class="cm-longtap-target"
                                    data-ca-longtap-action="setCheckBox"
                                    data-ca-longtap-target="input.cm-item"
                                    data-ca-id="{$file.name}"
                                >
                                    <td width="6%">
                                        <input type="checkbox" name="backup_files[]" value="{$file.name}" class="cm-item mobile-hide hide"/>
                                    </td>
                                    <td width="40%" data-th="{__("filename")}"><a href="{"datakeeper.getfile?file=`$file.name`"|fn_url}"><span>{$file.name}</span></a></td>
                                    <td width="15%" data-th="{__("date")}">{$file.create}</td>
                                    <td width="15%" data-th="{__("filesize")}">{$file.size|number_format}&nbsp;{__("bytes")}</td>
                                    <td width="10%" data-th="{__("type")}">{__($file.type)}</td>
                                    <td width="8%" class="nowrap" data-th="{__("tools")}">
                                        <div class="hidden-tools">
                                            {capture name="tools_list"}
                                                <li>{btn type="list" text=__("download") href="datakeeper.getfile?file=`$file.name`"}</li>
                                                {if $file.can_be_restored}
                                                    <li>{btn type="list" class="cm-confirm" text=__("restore") href="datakeeper.restore?backup_file=`$file.name`" method="POST"}</li>
                                                {/if}
                                                <li class="divider"></li>
                                                <li>{btn type="list" class="cm-confirm cm-ajax" text=__("delete") href="datakeeper.delete?backup_file=`$file.name`" data=["data-ca-target-id" => $result_ids] method="POST"}</li>
                                            {/capture}
                                            {dropdown content=$smarty.capture.tools_list}
                                        </div>
                                    </td>
                                </tr>
                            {/foreach}
                        </table>
                    </div>
                {/capture}

                {include file="common/context_menu_wrapper.tpl"
                    form="backups_form"
                    object="datakeeper"
                    items=$smarty.capture.datakeepers_table
                    is_check_all_shown=true
                }
            {else}
                <p class="no-items">{__("no_items")}</p>
            {/if}
            {/hook}
        </form>
        <!--backup_management--></div>
    {capture name="upload_backup"}
        {** UPLOAD BACKUP **}
        <div class="install-addon" id="content_upload_backup">
            <form action="{""|fn_url}" method="post" name="upload_backup_form" class="form-horizontal"
                  enctype="multipart/form-data">
                <input type="hidden" name="result_ids" value="theme_upload_container"/>

                <div class="install-addon-wrapper">
                    <img class="install-addon-banner" src="{$images_dir}/addon_box.png" width="151px" height="141px"/>
                    {include file="common/fileuploader.tpl" var_name="dump[0]" allowed_ext="zip,tgz,sql"}

                </div>

                <div class="buttons-container">
                    {include file="buttons/save_cancel.tpl" but_text=__("upload") but_name="dispatch[datakeeper.upload]" cancel_action="close" but_role="submit-link"}
                </div>
            </form>
        </div>
        {** /UPLOAD BACKUP **}
    {/capture}
    {include file="common/popupbox.tpl" id="upload_backup" text=__("upload_file") content=$smarty.capture.upload_backup link_class="cm-dialog-auto-size"}

    {capture name="buttons"}
        {capture name="tools_list"}
            <li>{btn type="list" text=__("logs") href="logs.manage"}</li>
            <li>{btn type="dialog" text=__("upload_file") target_id="content_upload_backup" form="upload_backup_form" class="cm-dialog-auto-size"}</li>
            <li><a href="{"datakeeper.optimize"|fn_url}" data-ca-target-id="elm_sidebar"
                class="cm-post cm-comet cm-ajax">{__("optimize_database")}</a></li>
        {/capture}
        {dropdown content=$smarty.capture.tools_list}
    {/capture}

    {capture name="adv_buttons"}
        {capture name="add_new_picker"}
            {** CREATE BACKUP **}
            <div id="content_backup">

                {include file="common/widget_copy.tpl"
                    widget_copy_title=__("tip")
                    widget_copy_text=__("datakeeper.run_backup_via_cron_message")
                    widget_copy_code_text="php /path/to/cart/"|fn_get_console_command:$config.admin_index:[
                        "dispatch"        => "datakeeper.backup",
                        "backup_database" => "Y",
                        "backup_files"    => "Y",
                        "dbdump_schema"   => "Y",
                        "dbdump_data"     => "Y",
                        "dbdump_tables"   => "all",
                        "extra_folders" => ["var/files", "var/attachments", "var/langs", "images"],
                        "p"
                    ]
                }

                <form action="{""|fn_url}" method="post" name="backup_form"
                      class="form-horizontal form-edit cm-ajax cm-comet cm-form-dialog-closer">
                    <input type="hidden" name="selected_section" value="backup"/>
                    <input type="hidden" name="result_ids" value="{$result_ids}"/>

                    <div class="control-group">
                        <label class="control-label" for="elm_backup_files">{__("backup_files")}:</label>

                        <div class="controls">
                            <label class="checkbox">
                                <input type="hidden" name="backup_files" value="N"/>
                                <input type="checkbox" name="backup_files" id="elm_backup_files" value="Y"
                                       onclick="Tygh.$('#files_backup_options').toggleBy();"/>
                            </label>
                        </div>
                    </div>

                    <div id="files_backup_options" class="hidden">
                        <hr>
                        <div class="control-group">
                            <label for="extra_folders" class="control-label">{__("extra_folders")}:</label>

                            <div class="controls">
                                <select name="extra_folders[]" id="extra_folders" multiple="multiple" size="5">
                                    <option value="images">images</option>
                                    <option value="var/files">var/files</option>
                                    <option value="var/attachments">var/attachments</option>
                                    <option value="var/langs">var/langs</option>
                                </select>

                                <p><a onclick="Tygh.$('#extra_folders').selectOptions(true); return false;"
                                      class="underlined">{__("select_all")}</a> / <a
                                            onclick="Tygh.$('#extra_folders').selectOptions(false); return false;"
                                            class="underlined">{__("unselect_all")}</a></p>
                            </div>
                        </div>
                        <hr>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="elm_backup_database">{__("backup_data")}:</label>

                        <div class="controls">
                            <label class="checkbox">
                                <input type="hidden" name="backup_database" value="N"/>
                                <input type="checkbox" name="backup_database" id="elm_backup_database" value="Y"
                                       checked="checked" onclick="Tygh.$('#database_backup_options').toggleBy();"/>
                            </label>
                        </div>
                    </div>

                    <div id="database_backup_options">
                        <hr>
                        <div class="control-group">
                            <label for="dbdump_tables" class="control-label">{__("select_tables")}:</label>

                            <div class="controls">
                                <select name="dbdump_tables[]" id="dbdump_tables" multiple="multiple" size="10">
                                    {foreach from=$all_tables item=tbl}
                                        <option value="{$tbl}"{if $config.table_prefix == '' || $tbl|strpos:$config.table_prefix === 0} selected="selected"{/if}>{$tbl}</option>
                                    {/foreach}
                                </select>

                                <p><a onclick="Tygh.$('#dbdump_tables').selectOptions(true); return false;"
                                      class="underlined">{__("select_all")}</a> / <a
                                            onclick="Tygh.$('#dbdump_tables').selectOptions(false); return false;"
                                            class="underlined">{__("unselect_all")}</a></p>

                                <div class="muted description">{__("multiple_selectbox_notice") nofilter}</div>
                            </div>
                        </div>

                        <div class="control-group">
                            <label for="dbdump_filename" class="control-label">
                                {__("backup_options")}:
                            </label>

                            <div class="controls">
                                <label for="dbdump_schema" class="checkbox">
                                    <input type="checkbox" name="dbdump_schema" id="dbdump_schema" value="Y"
                                           checked="checked">
                                    {__("backup_schema")}
                                    <p class="muted description">{__("tt_views_database_manage_backup_schema")}</p>
                                </label>
                                <label for="dbdump_data" class="checkbox">
                                    <input type="checkbox" name="dbdump_data" id="dbdump_data" value="Y"
                                           checked="checked">
                                    {__("backup_data")}
                                    <p class="muted description">{__("tt_views_database_manage_backup_data")}</p>
                                </label>
                            </div>
                        </div>
                        <hr>
                    </div>

                    <div class="control-group">
                        <label for="dbdump_filename" class="control-label">{__("backup_filename")}:</label>

                        <div class="controls">
                            <div class="input-append">
                                <input type="text" name="dbdump_filename" id="dbdump_filename" size="30"
                                       value="backup_{$smarty.const.PRODUCT_VERSION}_{"dMY_His"|date:$smarty.now}" class="input-text">
                                <span class="add-on">.zip</span>
                            </div>
                            <p class="muted description">{__("text_backup_filename_hint")}</p>
                        </div>
                    </div>

                    <div class="buttons-container">
                        {include file="buttons/save_cancel.tpl" but_name="dispatch[datakeeper.backup]" cancel_action="close" but_role="submit-link" but_meta="cm-comet"}
                    </div>
                </form>
            </div>
            {** /CREATE BACKUP **}
        {/capture}
        {if $backup_create_allowed}
            {include file="common/popupbox.tpl" id="create_backup" text=__("create_backup") title=__("create_backup") content=$smarty.capture.add_new_picker act="create" icon="icon-plus"}
        {/if}
    {/capture}

{/capture}
{include file="common/mainbox.tpl" title=__("backup_restore") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons}
