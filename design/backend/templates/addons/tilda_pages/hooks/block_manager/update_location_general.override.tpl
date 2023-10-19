{hook name="block_manager:update_location_general"}
    <div class="control-group">
        <label for="location_dispatch_{$html_id}" class="cm-required control-label">{__("dispatch")}: </label>
        <div class="controls"><select id="location_dispatch_{$html_id}_select" name="location_data[dispatch]" class="cm-select-with-input-key cm-reload-form">
                {foreach $dispatch_descriptions as $key => $value}
                    <option value="{$key}" {if $location.dispatch === $key}selected="selected"{$is_selected = true}{/if}>{$value}</option>
                    {if $location.dispatch === $key}
                        {$is_default_dispatch = true}
                    {/if}
                {/foreach}
                <option value="" {if !$is_selected}selected="selected"{/if}>{__("custom")}</option>
            </select>
            <input id="location_dispatch_{$html_id}" class="input-text{if $is_default_dispatch} input-text-disabled{/if}" {if $is_default_dispatch}disabled{/if} name="location_data[dispatch]" value="{$location.dispatch}" type="text"></div>
    </div>
    <div class="control-group">
        <label for="location_name" class="cm-required control-label">{__("name")}: </label>
        <div class="controls">
            <input id="location_name" type="text" name="location_data[name]" value="{$location.name}">
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="is_tilda_page">{__("tilda_pages.use_tilda_page")}:</label>
        <div class="controls">
            <span id="sw_tilda_settings_block" class="cm-combination">
                <input type="checkbox" name="location_data[is_tilda_page]" value="{"YesNo::YES"|enum}" {if $location.page_id} checked="checked"{/if}/>
            </span>
        </div>
    </div>

    <div id="tilda_settings_block" class="{if !$location.page_id}hidden{/if}">
        <div class="control-group">
            <label class="control-label" for="elm_tilda_page_id">{__("tilda_pages.tilda_page")}:</label>
            <div class="controls">
                <select 
                    name="location_data[tilda_page_id]" 
                    id="elm_tilda_page_id"
                >
                    {foreach $tilda_page_list as $page}
                        <option value="{$page.id}" {if $page.id === $location.page_id}selected="selected"{/if}>{$page.title}</option>
                    {/foreach}
                </select>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="is_only_content">{__("tilda_pages.use_top_panel")}:</label>
            <div class="controls">
                {include file="common/switcher.tpl"
                    input_name = "location_data[is_only_content]"
                    checked = $location.is_only_content === "YesNo::YES"|enum
                    input_id = "is_only_content"
                }
            </div>
        </div>

        {include file="addons/tilda_pages/views/tilda_pages/components/generate_block_element.tpl"}

        {if $location.published}
            <div class="control-group">
                <label class="control-label" for="elm_tilda_page_published">{__("tilda_pages.published")}:</label>
                <div class="controls">
                    <p id="elm_tilda_page_published">{$location.published|fn_timestamp_to_date}</p>
                </div>
            </div>
        {/if}
    </div>

    <div class="control-group">
        <label for="location_title" class="control-label">{__("page_title")}: </label>
        <div class="controls">
            <input id="location_title" type="text" name="location_data[title]" value="{$location.title}">
            {if $location.is_default}
                <div>
                    <label class="checkbox inline"><input type="checkbox" name="location_data[copy_translated][]" value="title" />{__("copy_to_other_locations")}</label>
                </div>
            {/if}
            <p class="muted description">{__("ttc_page_title")}</p>
        </div>
    </div>

    <div class="control-group">
        <label for="location_meta_descr" class="control-label">{__("meta_description")}: </label>
        <div class="controls">
            <textarea id="location_meta_descr" name="location_data[meta_description]" class="span9" cols="55" rows="4">{$location.meta_description}</textarea>
            {if $location.is_default}
            <label class="checkbox inline"><input type="checkbox" name="location_data[copy_translated][]" value="meta_description" />{__("copy_to_other_locations")}</label>
            {/if}
        </div>
    </div>

    <div class="control-group">
        <label for="location_meta_key" class="control-label">{__("meta_keywords")} </label>
        <div class="controls">
            <textarea id="location_meta_key" name="location_data[meta_keywords]" class="span9" cols="55" rows="4">{$location.meta_keywords}</textarea>
            {if $location.is_default}
            <label class="checkbox inline"><input type="checkbox" name="location_data[copy_translated][]" value="meta_keywords" />{__("copy_to_other_locations")}</label>
            {/if}
        </div>
    </div>

    <div class="control-group">
        <label for="location_custom_html" class="control-label">{__("head_custom_html")}</label>
        <div class="controls">
            <textarea id="location_custom_html" name="location_data[custom_html]" class="span9" cols="55" rows="4">{$location.custom_html}</textarea>
            {if $location.is_default}
                <label class="checkbox inline"><input type="checkbox" name="location_data[copy][]" value="custom_html" />{__("copy_to_other_locations")}</label>
            {/if}
            <p class="muted description">{__("tt_views_block_manager_update_location_head_custom_html") nofilter}</p>
        </div>
    </div>

    <div class="control-group">
        <label for="location_is_default" class="control-label">{__("default")} </label>
        <div class="controls">
            <input type="hidden" name="location_data[is_default]" value="N">
            <input type="checkbox" name="location_data[is_default]" value="Y" id="location_is_default" {if $location.is_default}checked="checked" disabled="disabled"{/if}>
            <p class="muted description">{__("tt_views_block_manager_update_location_default")}</p>
        </div>
    </div>

    <div class="control-group">
        <label for="location_position" class="control-label">{__("position")}: </label>
        <div class="controls">
            <input id="location_position" type="text" name="location_data[position]" value="{$location.position}">
        </div>
    </div>
{/hook}