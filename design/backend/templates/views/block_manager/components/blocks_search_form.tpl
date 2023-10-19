{if $in_popup}
    <div class="adv-search">
    <div class="group">
{else}
    <div class="sidebar-row">
    <h6>{__("admin_search_title")}</h6>
{/if}

{if $page_part}
    {$_page_part="#`$page_part`"}
{/if}

<form action="{""|fn_url}{$_page_part}" name="{$block_search_form_prefix}search_form" method="get" class="cm-disable-empty-all {$form_meta}" id="search_form">
<input type="hidden" name="type" value="{$search_type|default:"simple"}" autofocus="autofocus" />
{if $smarty.request.redirect_url}
    <input type="hidden" name="redirect_url" value="{$smarty.request.redirect_url}" />
{/if}
{if $selected_section != ""}
    <input type="hidden" id="selected_section" name="selected_section" value="{$selected_section}" />
{/if}
<input type="hidden" name="pcode_from_q" value="Y" />

{if $put_request_vars}
    {array_to_fields data=$smarty.request skip=["callback"]}
{/if}

{$extra nofilter}

{capture name="simple_search"}
    <div class="sidebar-field">
        <label for="name">{__("blocks")}</label>
        <input type="text" name="name" size="20" value="{$search.name}" />
    </div>

    <div class="sidebar-field">
        <div class="control-group">
            <label for="elm_type" class="control-label">{__("type")}</label>
            <div class="controls">
                <select name="type" id="elm_type">
                    <option value="">- {__("all_block_types")} -</option>
                    {foreach $block_types as $block_type}
                        <option value="{$block_type.type}"{if $block_type.type == $search.type}selected="selected"{/if}>{$block_type.name}</option>
                    {/foreach}
                </select>
            </div>
        </div>
    </div>

    <div class="sidebar-field">
        <div class="control-group">
            <label for="elm_layout_id" class="control-label">{__("layout")}</label>
            <div class="controls">
                <select name="layout_id" id="elm_layout_id">
                    <option value="">- {__("all_layouts")} -</option>
                    {foreach $layouts as $layout}
                        <option value="{$layout.layout_id}"{if $layout.layout_id == $search.layout_id}selected="selected"{/if}>{$layout.name} ({$layout.theme})</option>
                    {/foreach}
                </select>
            </div>
        </div>
    </div>
    <div class="sidebar-field">
        <div class="control-group">
            <label for="elm_location_id" class="control-label">{__("location")}</label>
            <div class="controls">
                <select name="location_id" id="elm_location_id">
                    <option value="">- {__("all_locations")} -</option>
                    {foreach $locations as $location}
                        <option value="{$location.location_id}"{if $location.location_id == $search.location_id}selected="selected"{/if}>{$location.layout_name} ({$location.theme_name}): {$location.name}</option>
                    {/foreach}
                </select>
            </div>
        </div>
    </div>
{/capture}

{capture name="advanced_search"}
{/capture}

{include file="common/advanced_search.tpl" simple_search=$smarty.capture.simple_search advanced_search=$smarty.capture.advanced_search dispatch=$dispatch view_type="blocks" in_popup=$in_popup}

<!--search_form--></form>
{if $in_popup}
    </div></div>
{else}
    </div><hr>
{/if}
