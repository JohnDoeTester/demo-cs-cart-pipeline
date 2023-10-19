{script src="js/tygh/block_manager.js"}

{capture name="mainbox"}

    <form action="{""|fn_url}" method="post" name="manage_blocks_form" id="manage_blocks_form" data-ca-main-content-selector="[data-ca-main-content]">
        <input type="hidden" name="return_url" value="{$config.current_url}">

        {include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id=$smarty.request.content_id}

        {$c_url=$config.current_url|fn_query_remove:"sort_by":"sort_order"}

        {$rev=$smarty.request.content_id|default:"pagination_contents"}
        {include_ext file="common/icon.tpl" class="icon-`$search.sort_order_rev`" assign=c_icon}
        {include_ext file="common/icon.tpl" class="icon-dummy" assign=c_dummy}
        {if $blocks}
            {capture name="block_manager_table"}
                <div class="table-responsive-wrapper longtap-selection">
                    <table width="100%" class="table table-middle table--relative table-responsive">
                        <thead
                            data-ca-bulkedit-default-object="true"
                            data-ca-bulkedit-component="defaultObject"
                        >
                        <tr>
                            <th class="left mobile-hide" width="6%">
                                {include file="common/check_items.tpl" is_check_all_shown=true}

                                <input type="checkbox"
                                    class="bulkedit-toggler hide"
                                    data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                                    data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                                />
                            </th>
                            <th width="9%"></th>
                            <th width="20%"><a class="cm-ajax" href="{"`$c_url`&sort_by=name&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("name")}{if $search.sort_by === "name"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a> /&nbsp;&nbsp;&nbsp; <a class="{$ajax_class}" href="{"`$c_url`&sort_by=type&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("type")}{if $search.sort_by === "type"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                            <th width="20%">{__("content")}</th>
                            <th width="30%" class="mobile-hide">{__("locations")}</th>
                            <th width="9%" class="center nowrap">{__("quantity")}</th>
                        </tr>
                        </thead>
                        <tbody>
                        {foreach $blocks as $block}
                            <tr class="cm-row-item "
                                data-ca-id="{$block.block_id}"
                                data-ca-longtap-action="setCheckBox"
                                data-ca-longtap-target="input.cm-item"
                            >
                                <td width="6%" class="left mobile-hide">
                                    <input type="checkbox" name="block_ids[]" value="{$block.block_id}" class="cm-item hide" />
                                </td>
                                <td width="9%" class="block-list__image">
                                    <div class="bmicon-{$block.type|replace:"_":"-"}"></div>
                                </td>
                                <td width="20%" class="block-name-column" data-th="{__("name")}">
                                    <input type="hidden" name="block_data[{$block.block_id}][block]" value="{$block.name}" />
                                    {include file="common/popupbox.tpl" id=$block.block_id link_text=$block.name title_start=__("editing_block") title_end=$block.name act="edit" href="block_manager.update_block?block_data[block_id]=`$block.block_id`&r_url=`$c_url|escape:url`" no_icon_link=true}<span class="muted"><small> #{$block.block_id}</small></span>
                                    <div class="block-list__labels">
                                        <div class="block-type">
                                            <span class="block-type__label muted">{$block_types.{$block.type}.name}</span>
                                        </div>
                                    </div>
                                </td>
                                <td width="20%" data-th="{__("content")}">
                                    <div class="row-status object-group-details">
                                        {$block.info.content nofilter}
                                    </div>
                                </td>
                                <td width="30%" class="mobile-hide" data-th="{__("locations")}">
                                    <div class="row-status object-group-details">
                                        {foreach $block.locations as $location}
                                            {$location.layout_name nofilter}&nbsp;({$location.theme_name nofilter}) : {", "|implode:$location.locations nofilter};
                                        {/foreach}
                                    </div>
                                </td>
                                <td width="9%" data-th="{__("quantity")}">
                                    <div class="row-status object-group-details center nowrap">
                                        {$block.quantity nofilter}
                                    </div>
                                </td>
                                <td width="9%" class="nowrap mobile-hide">
                                    <div class="hidden-tools">
                                        {capture name="tools_list"}
                                                <li>{include file="common/popupbox.tpl" id=$block.block_id link_text=__("edit") title_start=__("editing_block") title_end=$block.name act="edit" href="block_manager.update_block?block_data[block_id]=`$block.block_id`&r_url=block_manager.blocks" no_icon_link=true}</li>
                                                <li>{btn type="text" text=__("delete") href="block_manager.block.delete&block_id=`$block.block_id`" class="cm-confirm cm-tooltip cm-ajax cm-ajax-force cm-ajax-full-render cm-delete-row" data=["data-ca-target-id" => "pagination_contents"] method="POST"}</li>
                                        {/capture}
                                        {dropdown content=$smarty.capture.tools_list}
                                    </div>
                                </td>
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
            {/capture}

            {include file="common/context_menu_wrapper.tpl"
                form="manage_blocks_form"
                object="block_manager"
                items=$smarty.capture.block_manager_table
                is_check_all_shown=true
            }
        {else}
            <p class="no-items">{__("no_data")}</p>
        {/if}

        {capture name="adv_buttons"}
            {include file="common/popupbox.tpl"
                id="block_type_list"
                text=__("create_new_block")
                icon="icon-plus"
                act="general"
                href="block_manager.block_type_list?manage=Y&r_url={$c_url|escape:url}"
                opener_ajax_class="cm-ajax cm-ajax-force cm cm-add-block"
                content=""
            }
        {/capture}

        <div class="clearfix">
            {include file="common/pagination.tpl" div_id=$smarty.request.content_id}
        </div>

    </form>

{/capture}

{capture name="sidebar"}
        {include file="common/saved_search.tpl" dispatch="block_manager.blocks" view_type="blocks"}
        {include file="views/block_manager/components/blocks_search_form.tpl" dispatch="block_manager.blocks"}
{/capture}

{capture name="mainbox_title"}
    {__("manage_blocks")}
{/capture}

{include file="common/mainbox.tpl"
    title=$smarty.capture.mainbox_title
    content=$smarty.capture.mainbox
    adv_buttons=$smarty.capture.adv_buttons
    select_languages=true
    sidebar=$smarty.capture.sidebar
    content_id="manage_blocks"
}
