<tr class="import-field" id="field_{$id}">
    <td class="import-field__name" data-th="{__("advanced_import.column_header")}">
        <input type="hidden"
               name="fields[{$name|md5}][name]"
               value="{$name}"
        />
        <span data-ca-advanced-import-element="field">{$name}</span>
    </td>
    <td class="import-field__related_object" data-th="{__("advanced_import.product_property", ["[product]" => $smarty.const.PRODUCT_NAME])}">
        <input type="hidden"
               name="fields[{$name|md5}][related_object_type]"
               id="elm_field_related_object_type_{$id}"
        />
        <label class="cm-adv-import-placeholder cm-adv-import-placeholder--empty {if $view_only}cm-adv-import-placeholder--view_only{/if}"
            data-ca-advanced-import-field-id="{$id}"
            data-ca-advanced-import-select-name="fields[{$name|md5}][related_object]"
            data-ca-advanced-import-field-name="{$name}"
            data-ca-placeholder="-{__("none")}-"
            data-ca-view-only="{$view_only|default: "false"}"
        >-{__("none")}-</label>
        <input type="hidden" id="elm_field_related_object_{$id}" name="fields[{$name|md5}][related_object]" value=""/>
    </td>
    {if !$detailed_preset_page}
    <td class="import-field__preview" data-th="{__("advanced_import.first_line_import_value")}">
        {if $preview}
            {foreach $preview as $preview_item}
                <div class="import-field__preview-wrapper cm-show-more__wrapper">
                    <div class="import-field__preview-value cm-show-more__block">
                        {if $preset.fields.$name.modifier}
                                {$preview_item.$name.modified}
                            <div class="import-field__preview-info">
                                <a class="import-field__preview-button">{include_ext file="common/icon.tpl" class="icon-question-sign"}</a>
                                <div class="popover fade bottom in">
                                    <div class="arrow"></div>
                                    <h3 class="popover-title">{__("advanced_import.modifier_title")}</h3>
                                    <div class="popover-content">
                                        <div class="import-field__preview--original">
                                            <strong>{__("advanced_import.example_imported_title")}</strong>
                                            <p>{$preview_item.$name.original}</p>
                                        </div>
                                        <div class="import-field__preview--modified">
                                            <strong>{__("advanced_import.example_modified_title")}</strong>
                                            <p>{$preview_item.$name.modified}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        {else}
                            {$preview_item.$name.original}
                        {/if}
                    </div>
                </div>
            {/foreach}
            <div class="cm-show-more__btn">
                <a href="#" class="cm-show-more__btn-more">{__("advanced_import.show_more")}</a>
                <a href="#" class="cm-show-more__btn-less">{__("advanced_import.show_less")}</a>
            </div>
        {/if}
    </td>
    {/if}
    <td class="import-field__modifier" data-th="{__("advanced_import.modifier")}">
        <div class="control-group import-field__modifier-input-group">
            <input type="text"
                   name="fields[{$name|md5}][modifier]"
                   class="input-text input-hidden import-field__modifier-input"
                   placeholder="{__("advanced_import.modifier")}"
                   value="{$preset.fields.$name.modifier}"
                   data-ca-advanced-import-original-value="{$preview_item.$name.original|default:""}"
                   data-ca-advanced-import-element="modifier"
                   {if $view_only}disabled{/if}
            />
        </div>
    </td>
<!--field_{$id}--></tr>
