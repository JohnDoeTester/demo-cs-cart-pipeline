{$is_block_enabled = $block.status === "A" || !$block.status}
{$snapping_id = $snapping_id|default:$block.snapping_id}
{$location_id = $location_id|default:$parent_grid.location_id}
{$prefix = $prefix|default:""}
{$show_handler = $show_handler|default:true}
{$show_properties = $show_properties|default:true}
{$show_switch = $show_switch|default:true}
{$show_up = $show_up|default:true}
{$show_down = $show_down|default:true}
{$show_delete = $show_delete|default:false}
{$is_popup = $is_popup|default:false}
{$block_menu_compact = $block_menu_compact|default:false}
{$popup_title = $popup_title|default:""}
{$object_type = $object_type|default:"custom_block"}
{$extra_params = $extra_params|default:[]}
{$extra_params_query = ""}
{foreach $extra_params as $key=>$value}
    {$temp = "$key=$value"}
    {$extra_params_query = "$extra_params_query&$temp"}
{/foreach}

{if $smarty.const.AREA === "SiteArea::ADMIN_PANEL"|enum}
    {$block_properties_dispatch = "block_manager.update_custom_block?object_type=`$object_type`&block_id=`$snapping_id`&return_url=`$return_url``$extra_params_query`"}
    {$icons = [
        handler => "icon-ellipsis-vertical",
        properties => "icon-cog",
        activate => "icon-eye-open",
        deactivate => "icon-eye-close",
        move_up => "icon-arrow-up",
        move_down => "icon-arrow-down",
        delete => "icon-trash"
    ]}
{else}
    {$block_properties_dispatch = "block_manager.manage&selected_location=`$location_id`&object_id=`$snapping_id`&type=snapping"}
    {$icons = [
        handler => "ty-icon-handler",
        properties => "ty-icon-cog",
        activate => "ty-icon-eye-open",
        deactivate => "ty-icon-eye-close",
        move_up => "ty-icon-arrow-up",
        move_down => "ty-icon-arrow-down",
        delete => "ty-icon-trashcan"
    ]}
{/if}

<div class="bm-block-manager__menu-wrapper" data-ca-block-manager-menu-wrapper>
    <div class="bm-block-manager__menu {if $block_menu_compact}bm-block-manager__menu--compact{/if}" data-ca-block-manager-menu>
        {if $show_handler}
            <div class="bm-block-manager__handler">
                {include_ext file="common/icon.tpl"
                    class="`$icons.handler` bm-block-manager__icon"
                }
            </div>
        {/if}

        {if $show_properties}
            <a href="{fn_url($block_properties_dispatch, "A")}"
                class="bm-block-manager__btn bm-block-manager__properties {if $is_popup}cm-dialog-opener cm-dialog-destroy-on-close cm-ajax{/if}"
                {if $is_popup}
                    id="opener_{$prefix}_{$snapping_id}"
                    data-ca-target-id="content_{$prefix}_{$snapping_id}"
                    data-ca-dialog-title="{$popup_title}"
                {else}
                    target="_blank"
                {/if}
            >
                {include_ext file="common/icon.tpl"
                    class="`$icons.properties` bm-block-manager__icon"
                }
            </a>
        {/if}
        {if $show_switch}
            <button type="button"
                    class="bm-block-manager__btn bm-block-manager__switch {if !$is_block_enabled}bm-block-manager__block--disabled{/if}"
                    data-ca-block-manager-action="switch"
                    data-ca-block-manager-switch="{if $is_block_enabled}false{else}true{/if}"
                    {if $object_type === 'menu_item'}data-ca-block-manager-dispatch="custom_menu"{/if}
            >
                {$icon_activate = "`$icons.activate` bm-block-manager__icon{if !$is_block_enabled} bm-block-manager__icon--hidden{/if}"}
                {$icon_deactivate = "`$icons.deactivate` bm-block-manager__icon{if $is_block_enabled} bm-block-manager__icon--hidden{/if}"}
                {include_ext file="common/icon.tpl"
                    class=$icon_activate
                    data=[
                        "data-ca-block-manager-switch-icon" => "show"
                    ]
                }
                {include_ext file="common/icon.tpl"
                    class=$icon_deactivate
                    data=[
                        "data-ca-block-manager-switch-icon" => "hide"
                    ]
                }
            </button>
        {/if}
        {if $show_up}
            <button type="button" class="bm-block-manager__btn bm-block-manager__up"
                    data-ca-block-manager-action="move"
                    data-ca-block-manager-move="up"
                    {if $object_type === 'menu_item'}data-ca-block-manager-dispatch="custom_menu"{/if}
            >
                {include_ext file="common/icon.tpl"
                    class="`$icons.move_up` bm-block-manager__icon"
                }
            </button>
        {/if}
        {if $show_down}
            <button type="button"
                    class="bm-block-manager__btn bm-block-manager__down"
                    data-ca-block-manager-action="move"
                    data-ca-block-manager-move="down"
                    {if $object_type === 'menu_item'}data-ca-block-manager-dispatch="custom_menu"{/if}
            >
                {include_ext file="common/icon.tpl"
                    class="`$icons.move_down` bm-block-manager__icon"
                }
            </button>
        {/if}
        {if $show_delete}
            <button type="button"
                    class="bm-block-manager__btn bm-block-manager__delete"
                    data-ca-block-manager-action="delete"
                    {if $object_type === 'menu_item'}data-ca-block-manager-dispatch="custom_menu"{/if}
            >
                {include_ext file="common/icon.tpl"
                    class="`$icons.delete` bm-block-manager__icon"
                }
            </button>
        {/if}
    </div>
    <div class="bm-block-manager__arrow-wrapper">
        <div class="bm-block-manager__arrow"></div>
    </div>
</div>
