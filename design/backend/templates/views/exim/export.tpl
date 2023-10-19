<script>
(function(_, $) {
    _.tr('error_exim_layout_missed_fields', '{__("error_exim_layout_required_fields")|escape:"javascript"}');

    $(document).ready(function() {
        $(_.doc).on('click', '#exim_select_range', function(event){
            var pattern_id = $('.nav-tabs li.active').attr('id');
            $(this).attr('href', $(this).attr('href') + '&pattern_id=' + pattern_id);
        });
    });
}(Tygh, Tygh.$));
</script>

{if $pattern.range_options}
    {assign var="r_opt" value=$pattern.range_options}
    {assign var="r_url" value="exim.export?section=`$pattern.section`&pattern_id=`$pattern.pattern_id`"|fn_url}
    {assign var="oname" value=$r_opt.object_name|lower}
    {notes}
    {if $export_range}
        {__("text_objects_for_export", ["[total]" => $export_range, "[name]" => $oname]) nofilter}
        <p>
        <a href="{$r_opt.selector_url|fn_url}">{__("change_range")} &rsaquo;&rsaquo;</a>&nbsp;&nbsp;&nbsp;<a class="cm-post" href="{"exim.delete_range?section=`$pattern.section`&pattern_id=`$pattern.pattern_id`"|fn_url}">{__("delete_range")} &rsaquo;&rsaquo;</a>
        </p>
    {else}
        {__("text_select_range", ["[name]" => $oname])}: <a href="{"exim.select_range?section=`$pattern.section`"|fn_url}" id="exim_select_range">{__("select")} &rsaquo;&rsaquo;</a>
    {/if}
    {/notes}
{/if}

{capture name="mainbox"}

{capture name="tabsbox"}
{assign var="p_id" value=$pattern.pattern_id}
<div id="content_{$p_id}">
    {include file="common/subheader.tpl" title=__("general")}
    <form action="{""|fn_url}" method="post" name="{$p_id}_set_layout_form" class="form-horizontal form-edit">
    <input type="hidden" name="section" value="{$pattern.section}" />
    <input type="hidden" name="layout_data[pattern_id]" value="{$p_id}" />

    <div class="control-group">
        <label class="control-label">{__("layouts")}:</label>
        <div class="controls">
            {if $layouts}
                <select name="layout_data[layout_id]" id="s_layout_id_{$p_id}" class="cm-submit" data-ca-dispatch="dispatch[exim.set_layout]">
                    {foreach from=$layouts item=l}
                        <option value="{$l.layout_id}" {if $l.active == "Y"}{assign var="active_layout" value=$l}selected="selected"{/if}>{$l.name}</option>
                    {/foreach}
                </select>
                {include file="buttons/button.tpl" but_text=__("delete") but_name="dispatch[exim.delete_layout]" but_meta="cm-confirm"}

            {else}
                <p class="lowercase">{__("no_items")}</p>
            {/if}
        </div>
    </div>

    </form>

    <form action="{""|fn_url}" method="post" name="{$p_id}_manage_layout_form" class="cm-ajax cm-comet form-edit form-horizontal cm-disable-check-changes">
    <input type="hidden" name="section" value="{$pattern.section}" />
    <input type="hidden" name="layout_data[pattern_id]" value="{$p_id}" />
    <input type="hidden" name="layout_data[layout_id]" value="{$active_layout.layout_id}" />
    <input type="hidden" name="result_ids" value="content_{$p_id}" />

    {include file="views/exim/components/selectboxes.tpl" items=$pattern.export_fields assigned_ids=$active_layout.cols left_name="layout_data[cols]" left_id="pattern_`$p_id`" p_id=$p_id}

    {if $pattern.export_notice}<p>{$pattern.export_notice nofilter}</p>{/if}

    <div class="row-fluid shift-top export-save-layout">
        <div class="span6 form-inline">
            {include file="buttons/button.tpl" but_name="dispatch[exim.store_layout]" but_text=__("save_layout")}
            {__("or")}&nbsp;&nbsp;&nbsp;
            {include file="buttons/button.tpl" but_text=__("clear_fields") but_onclick="Tygh.$('#pattern_`$p_id`').moveOptions('#pattern_`$p_id`_right', `$ldelim`move_all: true`$rdelim`);" but_role="edit"}
        </div>
        <div class="span6">
            <div class="form-inline pull-right">
                <label for="layout_data">{__("save_layout_as")}:</label>
                <input type="text" id="layout_data" class="input-text valign" name="layout_data[name]" value="" />
                {include file="buttons/button.tpl" but_name="dispatch[exim.store_layout.save_as]" but_text=__("save")}
            </div>
        </div>
    </div>

    {include file="common/subheader.tpl" title=__("export_options")}
    {if $pattern.options}
        {foreach from=$pattern.options key=k item=o}
        {if !$o.import_only}
        <div class="control-group">
            <label for="{$p_id}_{$k}" class="control-label">
                {__($o.title)}:
            </label>
            <div class="controls">
                {if $o.type == "checkbox"}
                    <input type="hidden" name="export_options[{$k}]" value="N" />
                    <input id="{$p_id}_{$k}" type="checkbox" name="export_options[{$k}]" value="Y" {if $o.default_value == "Y"}checked="checked"{/if} />
                {elseif $o.type == "input"}
                    <input id="{$p_id}_{$k}" class="input-large" type="text" name="export_options[{$k}]" value="{$o.default_value}" />
                {elseif $o.type == "languages"}
                    <div class="checkbox-list shift-input">
                        {html_checkboxes name="export_options[lang_code]" options=$export_langs selected=$o.default_value columns=8}
                    </div>
                {elseif $o.type == "select"}
                    <select id="{$p_id}_{$k}" name="export_options[{$k}]">
                    {if $o.variants_function}
                        {foreach from=$o.variants_function|call_user_func key=vk item=vi}
                        <option value="{$vk}" {if $vk == $o.default_value}selected="selected"{/if}>{$vi}</option>
                        {/foreach}
                    {else}
                        {foreach from=$o.variants key=vk item=vi}
                        <option value="{$vk}" {if $vk == $o.default_value}selected="selected"{/if}>{__($vi)}</option>
                        {/foreach}
                    {/if}
                    </select>
                {/if}

                {if $o.notes}
                    <p class="muted description">{$o.notes nofilter}</p>
                {/if}

                {if $o.description}
                    <p class="muted description">{__($o.description)}</p>
                {/if}
            </div>
        </div>
        {/if}
        {/foreach}
    {/if}
    {assign var="override_options" value=$pattern.override_options}
    {if $override_options.delimiter}
        <input type="hidden" name="export_options[delimiter]" value="{$override_options.delimiter}" />
    {else}
    <div class="control-group">
        <label class="control-label">{__("csv_delimiter")}:</label>
        <div class="controls">
            {include file="views/exim/components/csv_delimiters.tpl" name="export_options[delimiter]" value=$active_layout.options.delimiter}
        </div>
    </div>
    {/if}
    {if $override_options.output}
        <input type="hidden" name="export_options[output]" value="{$override_options.output}" />
    {else}
    <div class="control-group">
        <label for="output" class="control-label">{__("output")}:</label>
        <div class="controls">
            {include file="views/exim/components/csv_output.tpl" name="export_options[output]" value=$active_layout.options.output}
            <p class="muted description">{__("tt_views_exim_export_output")}</p>
        </div>
    </div>
    {/if}
    <div class="control-group">
        <label for="filename" class="control-label">{__("filename")}:</label>
        <div class="controls">
            <input type="text" name="export_options[filename]" id="filename" size="50" class="input-large" value="{if $pattern.filename}{$pattern.filename}{else}{$p_id}_{$active_layout.name}_{$smarty.const.TIME|date_format:"%m%d%Y"}.csv{/if}" />
            {assign var="filename_description" value=$pattern.filename_description}
            {if $pattern.filename_description}<p class="muted description">{__($filename_description)}</p>{/if}

            <p class="muted description">
                {__('text_file_editor_notice', ["[href]" => "file_editor.manage?path=/"|fn_url]) nofilter}
            </p>
        </div>
    </div>
</form>
<!--content_{$p_id}--></div>

{/capture}
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox active_tab=$p_id}

{assign var="c_url" value=$config.current_url|escape:url}
<div class="hidden" title="{__("exported_files")}" id="content_exported_files">
{if $export_files}
    <div class="table-wrapper table-responsive-wrapper">
        <table class="table table-responsive">
        <thead>
            <tr>
                <th width="65%">{__("filename")}</th>
                <th width="20%">{__("filesize")}</th>
                <th width="15%">&nbsp;</th>
            </tr>
        </thead>
        <tbody>
        {foreach from=$export_files item=file name="export_files"}
        {assign var="file_name" value=$file.name|escape:"url"}
        <tr>
            <td data-th="{__("filename")}">
                <a href="{"exim.get_file?filename=`$file_name`"|fn_url}">{$file.name}</a></td>
            <td data-th="{__("filesize")}">
                {$file.size|number_format}&nbsp;{__("bytes")}</td>
            <td class="right" data-th="&nbsp;">
                <div class="hidden-tools">
                    <a href="{"exim.get_file?filename=`$file_name`"|fn_url}" title="{__("download")}" class="cm-tooltip btn">{include_ext file="common/icon.tpl" class="icon-download"}</a>
                    <a class="cm-ajax cm-confirm cm-post btn cm-tooltip" title="{__("delete")}" href="{"exim.delete_file?filename=`$file_name`&redirect_url=`$c_url`"|fn_url}" data-ca-target-id="content_exported_files">{include_ext file="common/icon.tpl" class="icon-trash"}</a>
                </div>
            </td>
        </tr>
        {/foreach}
        </tbody>
        </table>
    </div>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}
<!--content_exported_files--></div>

{capture name="buttons"}
    {capture name="tools_list"}
        <li>{btn type="dialog" text=__("exported_files") target_id="content_exported_files"}</li>
    {/capture}
    {dropdown content=$smarty.capture.tools_list}

    <div class="cm-tab-tools tab-tools pull-right shift-left" id="tools_{$p_id}">
        {include file="buttons/button.tpl" but_text=__("export") but_name="dispatch[exim.export]" but_role="submit-link" but_target_form="`$p_id`_manage_layout_form" but_meta="cm-tab-tools tab-tools cm-comet"}
        <!--tools_{$p_id}--></div>
{/capture}

{/capture}

{include file="common/mainbox.tpl"
    title=__("export_data")
    buttons=$smarty.capture.buttons
    content=$smarty.capture.mainbox
    select_storefront=true
    show_all_storefront=!("MULTIVENDOR"|fn_allowed_for)
}

{if $smarty.request.output == "D"}
<meta http-equiv="Refresh" content="0;URL={"exim.get_file?filename=`$smarty.request.filename`"|fn_url}" />
{/if}
