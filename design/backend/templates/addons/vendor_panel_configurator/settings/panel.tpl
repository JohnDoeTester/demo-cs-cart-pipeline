<div id="vendor_panel_config">
    <div id="color_schemas">
        <div class="control-group">
            <label class="control-label" for="vendor_panel_settings_color_schema">{__("theme_editor.color_scheme")}:</label>
            <div class="controls">
                <select name="vendor_panel[color_schema]" id="vendor_panel_settings_color_schema" class="js-vendor-panel-color-schema-input">
                    {foreach $color_schemas as $color_schema => $color_schema_data}
                        <option value="{$color_schema}" {if $vendor_panel.color_schema === $color_schema}selected{/if}>{__("vendor_panel_configurator.color_schema.{$color_schema}")}</option>
                    {/foreach}
                </select>
            </div>
        </div>
    </div>

    <div class="control-group">
        <label for="vendor_panel_element_color" class="control-label">{__("vendor_panel_configurator.element_color")}</label>
        <div class="controls">
            <div id="vendor_panel_element_color"
                 class="js-vendor-panel-configurator-colors-input colorpicker--wrapper"
                 data-target-input-name="vendor_panel_element_color_picker_{$num}"
                 data-target="element_color"
            >
                {include file="common/colorpicker.tpl"
                    cp_name="vendor_panel[element_color]"
                    cp_id="vendor_panel_element_color_picker_{$num}"
                    cp_value=$vendor_panel.element_color
                    show_picker=true
                    cp_meta="js-feature-variant-conditional-column"
                    cp_attrs=["data-ca-column-for-feature-style" => "ProductFeatureStyles::COLOR"|enum, "data-ca-column-for-filter-style" => "ProductFilterStyles::COLOR"|enum]
                }
            </div>
        </div>
    </div>
    <div class="control-group">
        <label for="vendor_panel_sidebar_color" class="control-label">{__("vendor_panel_configurator.sidebar_color")}</label>
        <div class="controls">
            <div id="vendor_panel_sidebar_color"
                 class="js-vendor-panel-configurator-colors-input colorpicker--wrapper"
                 data-target-input-name="vendor_panel_sidebar_color_picker_{$num}"
                 data-target="sidebar_color"
            >
                {include file="common/colorpicker.tpl"
                    cp_name="vendor_panel[sidebar_color]"
                    cp_id="vendor_panel_sidebar_color_picker_{$num}"
                    cp_value=$vendor_panel.sidebar_color
                    show_picker=true
                    cp_meta="js-feature-variant-conditional-column"
                    cp_attrs=["data-ca-column-for-feature-style" => "ProductFeatureStyles::COLOR"|enum, "data-ca-column-for-filter-style" => "ProductFilterStyles::COLOR"|enum]
                }
            </div>
        </div>
    </div>
    <div class="control-group">
        <label for="vendor_panel_background_image" class="control-label">{__("vendor_panel_configurator.sidebar_background_image")}</label>
        <div class="controls">
            {include file="common/attach_images.tpl"
                image_name="vendor_panel_background"
                image_object_type="vendor_panel"
                image_pair=$vendor_panel.main_pair
                image_object_id=0
                no_detailed=true
                hide_titles=true
                hide_alt=true
            }
            <p class="muted description">{__("vendor_panel_configurator.sidebar_background_image_description")}</p>
        </div>
    </div>
<!--vendor_panel_config--></div>
