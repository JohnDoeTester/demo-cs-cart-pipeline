{if $page_type === $smarty.const.PAGE_TYPE_TILDA_PAGE}
    {hook name="tilda_page:detailed_description"}
        <div class="control-group">
            <label class="control-label cm-required" for="elm_tilda_page_id">{__("tilda_pages.tilda_page")}:</label>
            <div class="controls">
                <select
                    name="page_data[tilda_page_id]"
                    id="elm_tilda_page_id"
                >
                    {foreach $tilda_page_list as $page}
                        <option value="{$page.id}" {if $page.id === $page_data.tilda_page_id}selected="selected"{/if}>{$page.title}</option>
                    {/foreach}
                </select>
            </div>
        </div>

        {if $view_uri}
            <div class="control-group">
                <div class="controls">
                        {include
                            file="buttons/button.tpl"
                            but_href="https://tilda.cc/page/?pageid=`$page_data.tilda_page_id`&projectid=`$page_data.tilda_project_id`"
                            but_text=__("tilda_pages.go_to_tilda")
                            but_role="action"
                            but_meta="btn-default"
                            but_target="_blank"
                        }
                </div>
            </div>
        {/if}

        <div class="control-group">
            <label class="control-label" for="is_only_content">{__("tilda_pages.use_top_panel")}:</label>
            <div class="controls">
                {include file="common/switcher.tpl"
                    input_name = "page_data[is_only_content]"
                    checked = $page_data.is_only_content === "YesNo::YES"|enum
                    input_id = "is_only_content"
                }
            </div>
        </div>

        {include file="addons/tilda_pages/views/tilda_pages/components/generate_block_element.tpl"}

        {if $view_uri}
            <div class="control-group">
                <label class="control-label" for="elm_tilda_page_published">{__("tilda_pages.published")}:</label>
                <div class="controls">
                    <p id="elm_tilda_page_published">{$page_data.tilda_published|fn_timestamp_to_date}</p>
                </div>
            </div>
        {/if}
    {/hook}
{/if}
