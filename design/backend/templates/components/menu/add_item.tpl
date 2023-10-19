{strip}
{if $runtime.customization_mode.block_manager && $location_data.is_frontend_editing_allowed}
{*
    $menu_name
    $id
    $has_subitems

    $link_class
    $popup_title
*}
    {$has_subitems = $has_subitems|default:false}
    {$is_subitem = $is_subitem|default:false}
    {$li_class = ($navigation_accordion) ? "accordion-group" : "dropdown"}
    {$extra_params = $extra_params|default:[]}
    {$extra_params_query = ""}
    {foreach $extra_params as $key=>$value}
        {$temp = "$key=$value"}
        {$extra_params_query = "$extra_params_query&$temp"}
    {/foreach}

    <li class="{$li_class} nav__header-main-menu-subitem bm-block-manager__block-add" data-ca-sortable-is-active="0">
        {if $is_subitem}
            {$link_class = "nav__menu-subitem"}
            {$popup_title = "{__("admin_menu.add_item_for_menu_title")}: `$menu_name`"}
        {else}
            {$link_class = "nav__menu-item"}
            {$popup_title = __("admin_menu.add_item_title")}
        {/if}

        {if $navigation_accordion && $has_items}
            {$link_class = "`$link_class` nav__menu-item--accordion"}
        {elseif !$is_subitem}
            {$link_class = "`$link_class` dropdown-toggle"}
        {/if}

        {include file="common/popupbox.tpl"
            act="edit"
            text=$popup_title
            link_text="{__("admin_menu.add_item")}..."
            href="block_manager.update_custom_block?object_type=menu_item&block_id=0&return_url=`$current_dispatch``$extra_params_query`"
            content=""
            id="`$id`_second_level_add"
            link_class="`$link_class` nav__menu-add"
        }
    </li>
{/if}
{/strip}