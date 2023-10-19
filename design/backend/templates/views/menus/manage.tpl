{script src="js/tygh/tabs.js"}

{capture name="mainbox"}
    <form action="{""|fn_url}" method="post" name="manage_menus_form" id="manage_menus_form">
        {$r_url = $config.current_url|escape:url}
        {$menus_statuses=""|fn_get_default_statuses:false}

        <div class="items-container" id="manage_tabs_list">
            {if $menus}
                {capture name="menus_table"}
                    <div class="table-responsive-wrapper longtap-selection">
                        <table class="table table-middle table--relative table-objects table-responsive table-responsive-w-titles">
                            <thead
                                data-ca-bulkedit-default-object="true"
                                data-ca-bulkedit-component="defaultObject"
                            >
                                <tr>
                                    <th width="6%" class="left mobile-hide" >
                                        {include file="common/check_items.tpl" check_statuses=$menus_statuses}

                                        <input type="checkbox"
                                            class="bulkedit-toggler hide"
                                            data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]" 
                                            data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                                        />
                                    </th>
                                    <th>{__("name")}</th>
                                    <th></th>
                                    <th></th>
                                    <th class="right">{__("status")}</th>
                                </tr>
                            </thead>

                            {foreach from=$menus item="menu"}
                                {$_href_delete = "menus.delete?menu_id=`$menu.menu_id`"}        
                                {$dialog_name = $menu.name}
                                {$name = $menu.name}
                                {$edit_link = "menus.update?menu_data[menu_id]=`$menu.menu_id`&return_url=$r_url"}
                                {$manage_items_link = "static_data.manage?section=A&menu_id=`$menu.menu_id`"}
                                
                                {capture name = "items_link"}            
                                    <li>{btn type="list" text=__("manage_items") href=$manage_items_link}</li>
                                    <li class="divider"></li>
                                {/capture}
                                
                                {include 
                                    file="common/object_group.tpl" 
                                    id=$menu.menu_id 
                                    text=$name 
                                    href=$edit_link 
                                    main_link=$manage_items_link 
                                    href_edit=$edit_link 
                                    href_delete=$_href_delete 
                                    delete_target_id="manage_tabs_list" 
                                    header_text=$dialog_name table="menus" 
                                    object_id_name="menu_id" 
                                    status=$menu.status 
                                    tool_items=$smarty.capture.items_link 
                                    no_table=true
                                    is_bulkedit_menu=true
                                    checkbox_col_width="6%"
                                    checkbox_name="menu_ids[]"
                                    show_checkboxes=true
                                    hidden_checkbox=true
                                    no_padding=true
                                }
                            {/foreach}
                        </table>
                    </div>
                {/capture}

                {include file="common/context_menu_wrapper.tpl"
                    form="manage_menus_form"
                    object="menus"
                    items=$smarty.capture.menus_table
                }
            {else}
                <p class="no-items">{__("no_data")}</p>
            {/if}
        <!--manage_tabs_list--></div>

        <div class="buttons-container">
            {capture name="extra_tools"}
                {hook name="currencies:import_rates"}{/hook}
            {/capture}
        </div>

        {capture name="adv_buttons"}
            {include file="common/popupbox.tpl"
                act="general"
                id="add_menu"
                text=__("new_menu")
                title=__("add_menu")
                act="general"
                href="menus.update"
                opener_ajax_class="cm-ajax"
                icon="icon-plus"
                content=""}
        {/capture}
    </form>
{/capture}

{include file="common/mainbox.tpl" title=__("menus") content=$smarty.capture.mainbox select_languages=true adv_buttons=$smarty.capture.adv_buttons}
