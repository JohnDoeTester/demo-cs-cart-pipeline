<div id="content_fields">
    {include file="addons/advanced_import/views/import_presets/components/fields_list.tpl"
        view_only=$view_only
    }
<!--content_fields--></div>
{if $show_buttons_container}
    <div class="buttons-container">
        {$allow_href_backup=$allow_href|default:false}
        {$allow_href=true}
        {include file="buttons/save_cancel.tpl"
                 cancel_action="close"
                 hide_first_button=!$fields|default:[]
                 but_text=__("import")
                 but_meta="cm-submit"
                 but_onclick="$.ceAdvancedImport('setFieldsForImport', `$preset.preset_id`)"
                 but_name="dispatch[advanced_import.import]"
        }
        {$allow_href=$allow_href_backup}
    </div>
{/if}