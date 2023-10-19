{if $addons.tilda_pages.tilda_public_api_key && $addons.tilda_pages.tilda_private_api_key}
    <div class="tilda_additional_settings">
        <div class="control-group setting-wide tilda_pages">
            <label class="control-label" for="elm_tilda_page_id">{__("tilda_pages.tilda_project")}:</label>
            <div class="controls">
                {foreach $field_item as $key => $item}
                    {if $item.name === "tilda_project_id"}
                        {$tilda_project_option_id = $key}
                    {/if}
                {/foreach}
                <select 
                    name="addon_data[options][{$tilda_project_option_id}]" 
                    id="elm_tilda_project"
                >
                    {foreach $tilda_project_list as $project}
                        <option value="{$project.id}" {if $project.id === $addons.tilda_pages.tilda_project_id}selected="selected"{/if}>{$project.title}</option>
                    {/foreach}
                </select>
            </div>
        </div>

        <div class="control-group setting-wide">
            <label class="control-label" for="elm_sync_link">{__("tilda_pages.auto_sync_title")}:</label>
            <div class="controls">
                <p id="elm_sync_link">{$auto_sync_link}</p>
            </div>
        </div>
    </div>
{else}
    <div class="control-group setting-wide tilda_pages">
        {__("tilda_pages.additional_settings_info")}
    </div>
{/if}