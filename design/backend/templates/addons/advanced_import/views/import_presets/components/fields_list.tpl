<script>
    Tygh.advanced_import = {
        relations: {$relations|json_encode nofilter},
        fields: {$fields|json_encode nofilter},
        preset_fields: {$preset.fields|default:[]|json_encode nofilter}
    };
</script>
{$detailed_preset_page = false}
<div data-ca-advanced-import-preset-file-extension="{$preset['file_extension']|default:""}"
     class="preview-fields-mapping__wrapper clearfix"
>

    <p class="pull-left p-notice">{__("advanced_import.fields_mapping.description", ["[product]" => $smarty.const.PRODUCT_NAME])}</p>
    <div class="btn-bar btn-toolbar pull-right">
        {include file="buttons/button.tpl"
            but_role="action1"
            but_target_id="advanced_import_modifiers_list_popup"
            but_text=__("advanced_import.modifiers_list")
            but_href="advanced_import.modifiers_list"
            but_meta="nav__actions-adv-buttons btn adv-buttons pull-right cm-dialog-opener"
        }
        <div id="advanced_import_modifiers_list_popup" class="hidden" title="{__("advanced_import.modifiers_list")}"></div>
    </div>

    <div class="clearfix"></div>

    <div class="span16 table-responsive-wrapper">
        <table width="100%" class="table table--relative table-responsive">
            <thead>
            <tr>
                <th class="import-field__name">
                    {__("advanced_import.column_header")}
                </th>
                <th class="import-field__related_object">
                    {__("advanced_import.product_property", ["[product]" => $smarty.const.PRODUCT_NAME])}
                </th>
                {if !$detailed_preset_page}
                    <th class="import-field__preview">
                        {__("advanced_import.first_line_import_value")}
                    </th>
                {/if}
                <th class="import-field__modifier">
                    {__("advanced_import.modifier")}
                </th>
            </tr>
            </thead>
            <tbody>
            {foreach $fields|default:[] as $id => $name}
                {include file="addons/advanced_import/views/import_presets/components/field.tpl"
                    view_only=$view_only
                    detailed_preset_page=$detailed_preset_page
                }
            {foreachelse}
                <tr>
                    <td colspan="4">
                        <p class="no-items">{__("no_data")}</p>
                    </td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    </div>

    <div class="clearfix"></div>
</div>
