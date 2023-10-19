{if $grid.grid_id}
    {$id = $grid.grid_id}
    {$elm_id = $id}
{else}
    {$id = 0}
    {$elm_id = uniqid()}
{/if}

<div id="grid_properties_{$elm_id}">
<form action="{""|fn_url}" method="post" enctype="multipart/form-data" class="form-horizontal form-edit " name="grid_update_form">
<input type="hidden" id="s_layout" name="s_layout" value="{$location.layout_id}" />
<input type="hidden" name="grid_id" value="{$id}" />

<input type="hidden" name="container_id" value="{$grid_params.container_id}" />
<input type="hidden" name="parent_id" value="{$grid_params.parent_id|default:$grid.parent_id|default:0}" />

<div class="tabs cm-j-tabs">
    <ul class="nav nav-tabs">
        <li class="cm-js active"><a>{__("general")}</a></li>
    </ul>
</div>

<div class="cm-tabs-content">
    <div class="control-group cm-no-hide-input">
        <label class="control-label" for="elm_grid_width_{$elm_id}">{__("width")}</label>
        <div class="controls">
            <select id="elm_grid_width_{$elm_id}" name="width" data-ca-grid-id="{$id}">
                {section name="width" start=$grid_params.min_width|default:1-1|default:0 loop=$grid_params.max_width|default:24}
                    {$index = $smarty.section.width.index + 1}
                    <option value="{$index}"
                            {if $index == $grid.width}selected="selected"{/if}
                    >{$index}</option>
                {/section}
            </select>
            <div>
                <span class="muted">{__("block_manager.description_available_values_for_grid_select")}</span>
            </div>
        </div>
    </div>

    <div class="control-group cm-no-hide-input">
        <label class="control-label" for="elm_grid_content_align_{$elm_id}">{__("content_alignment")}</label>
        <div class="controls">
        <select id="elm_grid_content_align_{$elm_id}" name="content_align">
            <option value="{"\Tygh\BlockManager\Grid::ALIGN_FULL_WIDTH"|constant}"
                    {if $grid.content_align == "\Tygh\BlockManager\Grid::ALIGN_FULL_WIDTH"|constant}selected="selected"{/if}
            >{__("full_width")}</option>
            <option value="{"\Tygh\BlockManager\Grid::ALIGN_LEFT"|constant}"
                    {if $grid.content_align == "\Tygh\BlockManager\Grid::ALIGN_LEFT"|constant}selected="selected"{/if}
            >{__("left")}</option>
            <option value="{"\Tygh\BlockManager\Grid::ALIGN_RIGHT"|constant}"
                    {if $grid.content_align == "\Tygh\BlockManager\Grid::ALIGN_RIGHT"|constant}selected="selected"{/if}
            >{__("right")}</option>
        </select>
        </div>
    </div>

    <div class="control-group cm-no-hide-input">
        <label class="control-label" for="elm_grid_wrapper_{$elm_id}">{__("wrapper")}</label>
        <div class="controls">
            <select id="elm_grid_wrapper_{$elm_id}" name="wrapper">
                <option value="">{__("none")}</option>
                {foreach $grids_schema.wrappers as $wrapper_name => $wrapper_template}
                    <option value="{$wrapper_template}" {if $wrapper_template == $grid.wrapper}selected{/if}>{$wrapper_name}</option>
                {/foreach}
            </select>
        </div>
    </div>

    <div class="control-group cm-no-hide-input">
        <label class="control-label" for="elm_grid_offset_{$elm_id}">{__("offset")}</label>
        <div class="controls">
            {$maxOffset = $grid_params.max_offset|default:$grid_params.max_width}
            <select id="elm_grid_offset_{$elm_id}" name="offset" data-ca-grid-id="{$id}">
                {section name="offset" start=0 loop=$maxOffset}
                    {assign var="index" value=$smarty.section.offset.index}
                    <option value="{$index}" {if $index == $grid.offset}selected="selected"{/if}>{$index}</option>
                {/section}
            </select>
            <div>
                <span class="muted">{__("block_manager.description_available_values_for_grid_select")}</span>
            </div>
        </div>
    </div>

    <div class="control-group cm-no-hide-input">
        <label class="control-label" for="elm_grid_user_class_{$elm_id}">{__("user_class")}</label>
        <div class="controls">
        <input id="elm_grid_user_class_{$elm_id}" name="user_class" value="{$grid.user_class}" type="text" />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label cm-required cm-multiple-checkboxes"
               for="grid_{$elm_id}_availability"
        >{__("block_manager.availability.show_on")}</label>
        <div class="controls" id="grid_{$elm_id}_availability">
            <div class="btn-group btn-group-checkbox">
                {foreach $grid.availability as $device => $is_available}

                    {if $device == "phone"}
                        {$devices_icon = "icon-mobile-phone"}
                    {elseif $device == "tablet"}
                        {$devices_icon = "icon-tablet"}
                    {elseif $device == "desktop"}
                        {$devices_icon = "icon-desktop"}
                    {/if}

                    <input type="checkbox"
                        id="elm_grid_{$elm_id}_show_on_{$device}"
                        class="cm-text-toggle btn-group-checkbox__checkbox"
                        {if $is_available}checked="checked"{/if}
                        data-ca-toggle-text="{$grid_availability_instance->getHiddenClass($device)}"
                        data-ca-toggle-text-mode="onDisable"
                        data-ca-toggle-text-target-elem-id="elm_grid_user_class_{$elm_id}"
                    />
                    <label class="btn btn-group-checkbox__label" for="elm_grid_{$elm_id}_show_on_{$device}">
                        {include_ext file="common/icon.tpl" class=$devices_icon}
                        {__("block_manager.availability.{$device}")}
                    </label>
                {/foreach}
            </div>
        </div>
    </div>

    {hook name="block_manager_update_grid:settings"}
    {/hook}

</div>

<div class="buttons-container">
    {include file="buttons/save_cancel.tpl" but_name="dispatch[block_manager.grid.update]" cancel_action="close" but_meta="cm-dialog-closer" save=$id}
</div>
</form>
<!--grid_properties_{$elm_id}--></div>
