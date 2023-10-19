{$show_layout_controls = !$dynamic_object.object_id && ("ULTIMATE"|fn_allowed_for || !$runtime.company_id)}
{$m_url = $smarty.request.manage_url|escape:"url"}
{$storefront_id = $storefront->storefront_id|default:0}

{script src="js/tygh/block_manager.js"}

<script class="cm-ajax-force">
    var selected_location = '{$location.location_id|default:0}';

    var dynamic_object_id = '{$dynamic_object.object_id|default:0}';
    var dynamic_object_type = '{$dynamic_object_scheme.object_type|default:''}';

    var BlockManager = new BlockManager_Class();

    // New traslations
    Tygh.tr({
        block_already_exists_in_grid: '{__("block_already_exists_in_grid")|escape:"javascript"}'
    });

    var editObjectId = {$edit_object_id|default:0|escape:javascript},
        editObjectType = '{$edit_object_type|default:''|escape:javascript}';

{literal}
    if (dynamic_object_id > 0) {
        var items = null;
        var grid_items = null;
    } else {
        var items = '.block';
        var grid_items = '.grid';
    }

    (function(_, $) {
        $(document).ready(function() {
            $('#content_location_' + selected_location).appear(function(){
                BlockManager.init('.grid', {
                    // UI settings
                    connectWith: '.grid',
                    items: items,
                    grid_items: grid_items,
                    revert: true,
                    placeholder: 'ui-hover-block',
                    opacity: 0.5,

                    // BlockManager_Class settings
                    parent: this,
                    container_class: 'container',
                    grid_class: 'grid',
                    block_class: 'block',
                    hover_element_class: 'hover-element',

                    // Controls selectors
                    controls_selector: '.grid-control-menu,.block-control-menu',
                    sortable_selector: '.ui-sortable',

                    device_availability_switcher: {
                        switch_selector: '.cm-switch-device-availability',
                        reset_selector: '.cm-reset-device-availability',
                        switcher_active_class: 'btn-primary',
                        device_attribute: 'data-ca-device-availability-device',
                        block_selector: '.device-specific-block',
                        block_availability_prefix: 'data-ca-device-availability-',
                        storage_cookie: 'device_filter'
                    },

                    edit_object_id: editObjectId,
                    edit_object_type: editObjectType
                });
            });
        });
    }(Tygh, Tygh.$));
{/literal}
</script>

{if $dynamic_object.object_id > 0}
    {style src="block_manager_in_tab.css"}
{/if}
{style src="lib/960/960.css"}

<div id="block_window" class="grid-block hidden"></div>
<div id="block_manager_menu" class="grid-menu hidden"></div>
<div id="block_manager_prop" class="grid-prop hidden"></div>

{include file="views/block_manager/render/grid.tpl" default_class="base-grid hidden" show_menu=true}
{include file="views/block_manager/render/block.tpl" default_class="base-block hidden" block_data=true}

{capture name="mainbox"}
{capture name="tabsbox"}
    {include file="views/block_manager/components/device_switch.tpl"
        container_id="content_location_{$location.location_id}"
    }
    <div class="block-manager-location-wrapper">
        <div class="block-manager-location" id="content_location_{$location.location_id}">
            {hook name="block_manager:manage_location"}
                {render_location
                    dispatch=$location.dispatch
                    location_id=$location.location_id
                    area='A'
                    lang_code=$location.lang_code
                    device=$smarty.request.device
                }
            {/hook}
        <!--content_location_{$location.location_id}--></div>
    </div>
{/capture}

{capture name="adv_buttons"}
    {capture name="add_new_picker"}
        {include file="views/block_manager/components/update_layout.tpl" layout_data=[]}
    {/capture}
    {if $show_layout_controls}
        {include file="common/popupbox.tpl" id="add_new_layout" text=__("new_layout") content=$smarty.capture.add_new_picker act="general" icon="icon-plus" title=__("add_layout")}
    {/if}
{/capture}

{capture name="export_layout"}
    {include file="views/block_manager/components/export_layout.tpl"}
{/capture}
{include file="common/popupbox.tpl" text=__("export_layout") content=$smarty.capture.export_layout id="export_layout_manager"}

{capture name="import_layout"}
    {include file="views/block_manager/components/import_layout.tpl"}
{/capture}
{include file="common/popupbox.tpl" text=__("import_layout") content=$smarty.capture.import_layout id="import_layout_manager"}

{capture name="buttons"}
    {* Display this buttons only on block manager page *}
    {if $show_layout_controls}
        {if $location.is_frontend_editing_allowed}
            {include file="buttons/button.tpl"
                but_href="customization.update_mode?type=block_manager&status=enable"
                but_text=__("edit_layout_on_site")
                but_role="action"
                but_meta="btn cm-post"
                but_target="_blank"
            }
        {/if}
        {capture name="tools_list"}
            <li>
                {btn type="list" text=__("preview") href="{"?s_layout=`$layout_data.layout_id`&storefront_id=`$storefront_id`"|fn_url:"C"}" target="_blank"}
            </li>
            <li class="divider"></li>
            {if !$layouts[$layout_data.layout_id].is_default}
                <li>{btn type="list" text=__("make_default") href="block_manager.set_default_layout?layout_id={$layout_data.layout_id}&from_layout_id={$layout_data.layout_id}" class="cm-ajax" data=["data-ca-target-id" => "actions_panel,block_manager_sidebar"] method="POST"}</li>
            {/if}
            <li>
                {capture name="add_new_picker"}
                    {include file="views/block_manager/components/update_layout.tpl" layout_data=$layouts[$layout_data.layout_id]}
                {/capture}
                {include file="common/popupbox.tpl" id="update_layout_{$layout_data.layout_id}" text=$layout_data.name content=$smarty.capture.add_new_picker act="link" link_text=__("properties")}
            </li>
            <li class="divider"></li>
            {if !$layouts[$layout_data.layout_id].is_default}
                <li>{btn type="list" text=__("delete") href="block_manager.delete_layout?layout_id=`$layout_data.layout_id`" class="cm-confirm" method="POST"}</li>
                <li class="divider"></li>
            {/if}
            <li>
                {include file="common/popupbox.tpl"
                id="export_layout_manager"
                link_text=__("export_layout")
                act="link"
                content=""
                general_class="action-btn"}
            </li>
            <li>
                {include file="common/popupbox.tpl"
                id="import_layout_manager"
                link_text=__("import_layout")
                act="link"
                link_class="cm-dialog-auto-size"
                content=""
                general_class="action-btn"
            }
            </li>
        {/capture}
        {dropdown content=$smarty.capture.tools_list}
    {/if}
{/capture}

{script src="js/tygh/tabs.js"}


<div class="cm-j-tabs tabs tabs-with-conf">
    <ul class="nav nav-tabs">
        <input type="hidden" id="s_layout" name="s_layout" value="{$location.layout_id}" />
        {foreach from=$navigation.tabs item=tab key=key name=tabs}
                <li id="{$key}{$id_suffix}" class="{if $tab.hidden == "Y"}hidden {/if}{if $key == "location_`$location.location_id`"}active extra-tab{/if}">
                    {if $key == "location_`$location.location_id`" && $show_layout_controls}
                        {btn type="dialog" class="cm-ajax-force hand icon-cog" href="block_manager.update_location?location=`$location.location_id`&s_layout=`$location.layout_id`" id="tab_location_`$location.location_id`" title=$tab.title}
                    {/if}
                    <a {if $tab.href}href="{$tab.href|fn_url}"{/if}>{$tab.title}</a>
                </li>
        {/foreach}
        {if $show_layout_controls}
            <li class="cm-no-highlight">
                {include file="common/popupbox.tpl"
                id="add_new_location"
                text=__("block_manager.new_layout_page")
                link_text="{__("block_manager.add_layout_page")}…"
                act="link"
                href="block_manager.update_location?s_layout=`$location.layout_id`"
                opener_ajax_class="cm-ajax"
                link_class="cm-ajax-force"
                icon="icon-plus"
                content=""}</li>
        {/if}
    </ul>
</div>
<div class="cm-tabs-content">
    {$smarty.capture.tabsbox nofilter}
</div>

{/capture}

{capture name="sidebar"}
    {hook name="block_manager:manage_sidebar"}
    {if (count($layouts) > 1)}
        <div id="block_manager_sidebar">
            <div class="sidebar-row layouts">
                <h6>{__("switch_layout")}</h6>
                <ul class="nav nav-list">
                    {foreach $layouts as $layout}
                        <li class="with-menu {if $layout.layout_id == $runtime.layout.layout_id} active{/if}">
                            {capture name="tools_list"}
                                <li>{btn type="list" text=__("preview") href="{"?s_layout=`$layout.layout_id`&storefront_id=`$storefront_id`"|fn_url:"C"}" target="_blank"}</li>
                                {if $show_layout_controls}
                                    <li class="divider"></li>
                                    {if !$layout.is_default}
                                    <li>{btn type="list" text=__("make_default") href="block_manager.set_default_layout?layout_id={$layout.layout_id}&from_layout_id={$layout_data.layout_id}" class="cm-ajax" data=["data-ca-target-id" => "actions_panel,block_manager_sidebar"] method="POST"}</li>
                                    {/if}
                                    <li>
                                        {capture name="add_new_picker"}
                                            {include file="views/block_manager/components/update_layout.tpl" layout_data=$layout}
                                        {/capture}
                                        {include file="common/popupbox.tpl" id="update_layout_sidebar_{$layout.layout_id}" text=$layout.name content=$smarty.capture.add_new_picker act="link" link_text=__("properties")}
                                    </li>

                                    {if !$layout.is_default}
                                        <li class="divider"></li>
                                        <li>{btn type="list" text=__("delete") href="block_manager.delete_layout?layout_id=`$layout.layout_id`" class="cm-confirm" method="POST"}</li>
                                    {/if}
                                {/if}
                            {/capture}
                            <div class="pull-right">
                                {dropdown content=$smarty.capture.tools_list}
                            </div>
                            <a href="{"block_manager.manage?s_layout=`$layout.layout_id`"|fn_url}">{$layout.name}</a>
                        </li>
                    {/foreach}
                </ul>
            </div>
            <hr>
            {hook name="layouts:sidebar"}
            {/hook}

        <!--block_manager_sidebar--></div>
    {/if}
    <div class="sidebar-row layouts">
        <h6>{__("layout_page")}</h6>
        <ul class="unstyled">
            {hook name="layouts:layout_page_sidebar"}
                <li>{__("dispatch")}: <span class="wrap">{$location.dispatch}</span></li>
                <li>{__("name")}: <span class="wrap-word">{$location.name}</span></li>
            {/hook}
        </ul>

        <div class="sidebar-field text-right">
            {btn type="dialog"
                text=__("layout_meta_data")
                class="cm-dialog-opener right"
                href="{"block_manager.update_location?location=`$location.location_id`&s_layout=`$location.layout_id`"|fn_url}"
                title="{$location.name}"
            }
        </div>
    </div>
    <hr>
    {/hook}
{/capture}

{if $dynamic_object.object_id}
    {if ($location)}
        {$smarty.capture.mainbox nofilter}
    {elseif fn_check_permissions("block_manager", "update_location", "admin")}
        {if $location_by_dispatch|default:[]}
            {__("block_manager.manage_layout_in_tab_unavailable_by_object_id", [
                "[entity]"         => __("block_manager.dynamic_entity_{$dynamic_object.object_type}"),
                "[link]"           => fn_url("block_manager.manage?selected_location=`$location_by_dispatch.location_id`", "A"),
                "[location_name]"  => $location_by_dispatch.name,
                "[entity_tab]"     => __("{$dynamic_object.object_type}")
            ]) nofilter}
        {else}
            {__("block_manager.manage_layout_in_tab_not_exist_location", [
                "[entity]"         => __("block_manager.dynamic_entity_{$dynamic_object.object_type}"),
                "[link]"           => fn_url("block_manager.manage?s_layout=`$layout.layout_id`", "A"),
                "[dispatch_value]" => $dynamic_object_scheme.customer_dispatch,
                "[entity_tab]"     => __("{$dynamic_object.object_type}")
            ]) nofilter}
        {/if}
    {else}
        {__("block_manager.manage_layout_in_tab_unavailable", [
            "[entity]" => __("block_manager.dynamic_entity_{$dynamic_object.object_type}")
        ]) nofilter}
    {/if}
{else}
    {include file="common/mainbox.tpl"
        title_start=__("editing_layout")
        title_end=$layout_data.name
        adv_buttons=$smarty.capture.adv_buttons
        buttons=$smarty.capture.buttons
        content=$smarty.capture.mainbox
        select_languages=true
        sidebar=$smarty.capture.sidebar
        mainbox_content_wrapper_class="block-manager-wrapper"
        select_storefront=true
        show_all_storefront=false
    }
{/if}
