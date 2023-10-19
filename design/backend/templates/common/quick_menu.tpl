{if "tools.show_quick_menu"|fn_check_view_permissions}

<script>
    Tygh.tr('editing_quick_menu_section', '{__("editing_quick_menu_section")|escape:"javascript"}');
    Tygh.tr('new_section', '{__("new_section")|escape:"javascript"}');
    Tygh.tr('editing_quick_menu_link', '{__("editing_quick_menu_link")|escape:"javascript"}');
    Tygh.tr('new_link', '{__("new_link")|escape:"javascript"}');
</script>

<div class="quick-menu-container" id="quick_menu">
    <div class="quick-menu quick-menu-show-on-hover">
        <a id="sw_quick_menu_content" class="quick-menu-link {if $edit_quick_menu || $expand_quick_menu}open{/if} cm-combination btn">
            <span class="quick-menu-link__text">{__("quick_menu")} <b class="caret"></b></span>
            {include_ext file="common/icon.tpl"
                class="icon-ellipsis-vertical quick-menu-link__icon"
            }
        </a>

        <div id="quick_menu_content" class="quick-menu-content cm-popup-box{if !$edit_quick_menu && !$expand_quick_menu} hidden{/if}">
        {if $edit_quick_menu}
            <div class="menu-container">
                <div class="table-wrapper">
                    <table width="100%">
                        {foreach from=$quick_menu key=sect_id item=sect}
                            <tr data-ca-qm-item="{$sect_id}" data-ca-qm-parent-id="0"
                                data-ca-qm-position="{$sect.section.position}">
                                <td class="nowrap section-header">
                                    <strong class="cm-qm-name">{$sect.section.name}</strong>
                                </td>
                                <td class="hidden-tools nowrap right">
                                    {include_ext file="common/icon.tpl"
                                        class="icon-trash hand valign cm-delete-section"
                                        title=__("remove_this_item")
                                    }
                                    {include_ext file="common/icon.tpl"
                                        class="icon-edit hand cm-update-item"
                                    }
                                </td>
                            </tr>
                            {foreach from=$sect.subsection item=subsect}
                                <tr data-ca-qm-item="{$subsect.menu_id}" data-ca-qm-parent-id="{$subsect.parent_id}"
                                    data-ca-qm-position="{$subsect.position}">
                                    <td class="nowrap">
                                        <a class="cm-qm-name" href="{$subsect.url|fn_url}">{$subsect.name}</a>
                                    </td>
                                    <td class="hidden-tools nowrap right">
                                        {include_ext file="common/icon.tpl"
                                            class="icon-trash hand valign cm-delete-section"
                                            title=__("remove_this_item")
                                        }
                                        {include_ext file="common/icon.tpl"
                                            class="icon-edit hand cm-update-item"
                                        }
                                    </td>
                                </tr>
                            {/foreach}
                            <tr data-ca-qm-item="{$sect_id}" data-ca-qm-parent-id="0"
                                data-ca-qm-position="{$sect.section.position}">
                                <td colspan="2" class="cm-add-link"><a class="edit cm-add-link">{__("add_link")}</a></td>
                            </tr>
                        {/foreach}
                    </table>
                </div>
            </div>
            <div class="table-wrapper">
            <table width="100%" class="quick-menu-edit">
                <tr>
                    <td class="nowrap top">
                        <a class="edit cm-add-section">{__("add_section")}</a>
                        <a class="pull-right edit cm-ajax" data-ca-target-id="quick_menu"
                           href="{"tools.show_quick_menu"|fn_url}"
                           data-ca-event="ce.quick_menu_content_switch_callback">{__("done")}</a>
                    </td>
                </tr>
            </table>
            </div>
        {else}
            {if $quick_menu}
                <div class="menu-container">
                    <ul class="unstyled">
                        {foreach from=$quick_menu item=sect}
                            <li><span>{$sect.section.name}</span></li>
                            {foreach from=$sect.subsection item=subsect}
                                <li><a href="{$subsect.url|fn_url}">{$subsect.name}</a></li>
                            {/foreach}
                        {/foreach}
                    </ul>
                </div>
            {/if}
            <div class="quick-menu-actions right">
                <a class="edit cm-ajax" href="{"tools.show_quick_menu.edit"|fn_url}" data-ca-target-id="quick_menu"
                   data-ca-event="ce.quick_menu_content_switch_callback" title="{__("edit")}">{include_ext file="common/icon.tpl" class="icon-edit hand"}</a>
            </div>
        {/if}
    </div>
    </div>

    {if $show_quick_popup}
        <div id="quick_box" class="hidden quick-menu-popup cm-dialog-auto-size" data-ca-target-id="quick_box">

            <div id="quick_menu_language_selector">
                {include file="common/select_object.tpl"
                    style="graphic"
                    link_tpl="tools.get_quick_menu_variant"|fn_link_attach:"descr_sl="
                    items=$languages
                    selected_id=$smarty.const.DESCR_SL
                    key_name="name"
                    suffix="quick_menu"
                    display_icons=true
                    select_container_id="quick_menu_language_selector"
                }
            </div>

            <form class="cm-ajax form-horizontal form-edit" name="quick_menu_form" action="{""|fn_url}" method="post">
                <input id="qm_item_id" type="hidden" name="item[id]" value=""/>
                <input id="qm_item_parent" type="hidden" name="item[parent_id]" value="0"/>
                <input id="qm_descr_sl" type="hidden" name="descr_sl" value=""/>
                <input type="hidden" name="result_ids" value="quick_menu"/>

                <div class="control-group">
                    <label class="cm-required control-label" for="qm_item_name">{__("name")}:</label>

                    <div class="controls">
                        <input id="qm_item_name" name="item[name]" type="text" value="" size="40"/>
                    </div>
                </div>

                <div class="control-group">
                    <label class="cm-required control-label" for="qm_item_link">{__("link")}:</label>

                    <div class="controls">
                        <input id="qm_item_link" name="item[url]" class="input-text-large" type="text" value=""
                               size="40"/>
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="qm_item_position">{__("position")}:</label>

                    <div class="controls">
                        <input id="qm_item_position" name="item[position]" type="text" value="" size="6"/>
                    </div>
                </div>

                <div class="control-group">
                    <div class="controls">
                        <a id="qm_current_link">{__("use_current_link")}</a>
                    </div>
                </div>

                <div class="buttons-container">
                    {include file="buttons/save_cancel.tpl" but_name="dispatch[tools.update_quick_menu_item.edit]" cancel_action="close" save=true}
                </div>

            </form>
        </div>
    {/if}
    <!--quick_menu--></div>
{/if}
