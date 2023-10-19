<div class="tabs cm-j-tabs vendor-panel-configurator-tabs">
    <ul class="nav nav-tabs">
        {foreach $product_page_configuration as $tab_id => $tab name="product_tab"}
            {$title = $tab.title|default:"vendor_panel_configurator.product.{$tab_id}"}
            <li id="product_tab_{$tab_id}"
                class="cm-js vendor-panel-configurator-tab {if $product_tab.first}active{/if}"
            >
                <a>
                    {__($title)}
                    <input type="hidden"
                           name="product_tabs_configuration[{$tab_id}]"
                           {if $tab.is_optional}
                               value="0"
                           {else}
                               value="1"
                           {/if}
                    />
                    <input type="checkbox"
                           name="product_tabs_configuration[{$tab_id}]"
                           class="vendor-panel-configurator-tab__selector {if $tab.is_optional}vendor-panel-configurator-tab__selector--optional{/if}"
                           value="1"
                           data-ca-stop-event-propagation="true"
                           {if !$tab.is_optional}
                               disabled="disabled"
                           {/if}
                           {if $tab.is_visible|default:true}
                               checked
                           {/if}
                    />
                </a>
            </li>
        {/foreach}
    </ul>
</div>
{foreach $product_page_configuration as $tab_id => $tab name="product_tab"}
    <div id="content_product_tab_{$tab_id}" class="{if !$product_tab.first}hidden{/if}">
        {foreach $tab.sections as $section_id => $section}
            {$title = $section.title|default:"simple_admin_panel.product.{$tab_id}.{$section_id}"}
            {include file = "common/subheader.tpl" title = __($title) target="#section_{$tab_id}_{$section_id}"}
            <div id="section_{$tab_id}_{$section_id}" class="in collapse">
                {foreach $section.fields as $field_id => $field}
                    {$title = $field.title|default:"vendor_panel_configurator.product.tabs.{$tab_id}.{$section_id}.{$field_id}"}
                    <div class="control-group vendor-panel-configurator-field">
                        <label for="product_field_{$tab_id}_{$section_id}_{$field_id}" class="control-label">
                            {__($title)}
                        </label>
                        <div class="controls">
                            <input type="hidden"
                                   name="product_fields_configuration[{$tab_id}][{$section_id}][{$field_id}]"
                                   {if $field.is_optional}
                                       value="0"
                                   {else}
                                       value="1"
                                   {/if}
                            />
                            {if !$field.is_global|default:false}
                                <input id="product_field_{$tab_id}_{$section_id}_{$field_id}"
                                       name="product_fields_configuration[{$tab_id}][{$section_id}][{$field_id}]"
                                       type="checkbox"
                                       value="1"
                                        {if !$field.is_optional}
                                            disabled="disabled"
                                        {/if}
                                        {if $field.is_visible|default:true}
                                            checked="checked"
                                        {/if}
                                />
                            {else}
                                {$settings_url = "settings.manage&section_id=`$field.section`&highlight=default_`$field_id`"|fn_url}
                                <p>{__("vendor_panel_configurator.global_setting_warning", ["[settings_url]" => $settings_url]) nofilter}</p>
                            {/if}
                        </div>
                    </div>
                {/foreach}
            </div>
        {foreachelse}
            <p class="no-items">{__("vendor_panel_configurator.unconfigurable_tab")}</p>
        {/foreach}
    </div>
{/foreach}
