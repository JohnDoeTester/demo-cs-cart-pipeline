{$id = ($tab_data) ? $tab_data.tab_id : 0}
{$available_in_popup = $tab_data.available_in_popup|default:true}
{script src="js/tygh/tabs.js"}
{script src="js/tygh/block_manager.js"}

{$html_id = "tab_`$id`"}

<script>
    var html_id = "{$html_id}";
    var BlockManager = new BlockManager_Class();
    
    {literal}
    (function(_, $) {
        $(document).ready(function() {
            var is_manage_blocks = true;
            var result_id = 'ajax_update_block_' + html_id;

            BlockManager.initBlockManagerActions(is_manage_blocks, result_id);
        });

    }(Tygh, Tygh.$));
    {/literal}
</script>

<form action="{""|fn_url}" enctype="multipart/form-data" name="update_product_tab_form_{$id}" method="post" class=" form-horizontal">
<div id="content_group_{$html_id}">
    <input type="hidden" name="tab_data[tab_id]" value="{$id}" />
    <input type="hidden" name="result_ids" value="content_group_tab_{$id}" />

    <div class="tabs cm-j-tabs">
        <ul class="nav nav-tabs">
            <li id="general_{$html_id}" class="cm-js{if $active_tab == "block_general_`$html_id`"} active{/if}">
                <a>{__("general")}</a>
            </li>
            {if $dynamic_object_scheme && $id > 0}
                <li id="tab_status_{$html_id}" class="cm-js{if $active_tab == "block_status_`$html_id`"} active{/if}">
                    <a>{__("status")}</a>
                </li>
            {/if}
        </ul>
    </div>

    <div class="cm-tabs-content" id="tabs_content_{$html_id}">
        <div id="content_general_{$html_id}">
            <fieldset>
                <div class="control-group">
                    <label class="cm-required control-label" for="elm_description_{$html_id}">{__("name")}:</label>
                    <div class="controls">
                        <input type="text" name="tab_data[name]" value="{$tab_data.name}" id="elm_description_{$html_id}" class="input-text" size="18" />
                    </div>
                </div>

                {if !$dynamic_object_scheme}
                    {include file="common/select_status.tpl" input_name="tab_data[status]" id="elm_tab_data_`$html_id`" obj=$tab_data}
                {/if}

                {if $available_in_popup}
                    <div class="control-group">
                        <label for="elm_show_in_popup_{$html_id}" class="control-label">{__("show_tab_in_popup")}:</label>
                        <div class="controls">
                            <input type="hidden" name="tab_data[show_in_popup]" value="N" />
                            <input type="checkbox" name="tab_data[show_in_popup]" id="elm_show_in_popup_{$html_id}" {if $tab_data.show_in_popup == "Y"}checked="checked"{/if} value="Y">
                        </div>
                    </div>
                {/if}

                {if $tab_data.is_primary !== 'Y' && "block_manager.update_block"|fn_check_view_permissions}
                    <div class="control-group">
                        <label for="elm_block_{$html_id}" class="cm-required control-label">{__("block")}:</label>
                        <div class="controls clearfix help-inline-wrap">
                            {include file="common/popupbox.tpl"
                                act="general"
                                id="select_block_`$html_id`"
                                text=__("select_block")
                                link_text=__("select_block")
                                href="block_manager.block_selection?extra_id=`$tab_data.tab_id`&on_product_tabs=1"
                                action="block_manager.block_selection"
                                opener_ajax_class="cm-ajax cm-ajax-force"
                                content=""
                                meta="pull-left"
                            }
                            <br><br>
                            <div id="ajax_update_block_{$html_id}">
                                <input type="hidden" name="block_data[block_id]" id="elm_block_{$html_id}" value="{$tab_data.block_id|default:''}" />
                                {if $tab_data.block_id > 0}
                                    {include file="views/block_manager/render/block.tpl" block_data=$block_data external_render=true
                                    external_id=$html_id}
                                {/if}
                            <!--ajax_update_block_{$html_id}--></div>
                        </div>
                    </div>
                {/if}
            </fieldset>
        </div>
        {if $dynamic_object_scheme && $id > 0}
            <div id="content_tab_status_{$html_id}" >
                <fieldset>
                    <div class="control-group">
                        <label class="control-label">{__("global_status")}:</label>
                        <div class="controls">
                            <label class="radio text-value">{if $tab_data.status == 'A'}{__("active")}{else}{__("disabled")}{/if}</label>
                        </div>
                    </div>
                    <input type="hidden" class="cm-no-hide-input" name="snapping_data[object_type]" value="{$dynamic_object_scheme.object_type}" />
                    <div class="control-group">
                        <label class="control-label">{if $tab_data.status == 'A'}{__("disable_for")}{else}{__("enable_for")}{/if}:</label>
                        <div class="controls">
                            {include_ext
                                file=$dynamic_object_scheme.picker
                                data_id="tab_`$html_id`_product_ids"
                                input_name="tab_data[product_ids]"
                                item_ids=$tab_data.product_ids
                                view_mode="links"
                                params_array=$dynamic_object_scheme.picker_params
                            }
                        </div>
                    </div>
                </fieldset>
            <!--content_tab_status_{$html_id}--></div>
        {/if}
    </div>

<!--content_group_{$html_id}--></div>
<div class="buttons-container">
    {include file="buttons/save_cancel.tpl" but_name="dispatch[tabs.update]" cancel_action="close" save=$id}
</div>
</form>
