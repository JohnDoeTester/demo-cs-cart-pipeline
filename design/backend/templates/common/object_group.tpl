{$is_responsive_table_without_title = $is_responsive_table_without_title|default:true}

{if !$no_table}
<div class="{if $is_responsive_table}table-responsive-wrapper{else}table-wrapper{/if}">
    <table width="100%" class="table table-middle table--relative table-objects {if $is_responsive_table}table-responsive {if $is_responsive_table_without_title}table-responsive-w-titles{/if}{/if} {if $table_striped} table-striped{/if}">
        <tbody>
    {/if}
        <tr class="cm-row-status-{$status|default:""|lower} {$additional_class} cm-row-item {if $bulkedit_disabled_notice}longtap-selection-disable{/if}"
                {if $row_id}id="{$row_id}"{/if}
                data-ct-{$table}="{$id}"
                {if $is_bulkedit_menu}
                    data-ca-longtap-action="setCheckBox"
                    data-ca-longtap-target="input.cm-item"
                    data-ca-id="{$id}"
                    {if $bulkedit_menu_category_ids}
                        data-ca-category-ids="{$bulkedit_menu_category_ids}"
                    {/if}
                    {if $bulkedit_disabled_notice}
                        data-ca-bulkedit-disabled-notice="{$bulkedit_disabled_notice}"
                    {/if}
                {/if}
            >
            {if $checkbox_name && $show_checkboxes}
                <td 
                    {if $checkbox_col_width} width="{$checkbox_col_width}" {/if}
                    data-th="&nbsp;"
                    class="{$checkbox_col_class}"
                >
                    <input type="checkbox" name="{$checkbox_name}" value="{$checkbox_value|default:$id}"{if $checked} checked="checked"{/if} class="cm-item{if $hidden_checkbox} hidden{/if} cm-item-status-{$status|lower}" />
                </td>
            {/if}

                {if !$no_padding}
                <td width="1%" class="no-padding-td" data-th="&nbsp;">
                    {if $draggable}
                        <span class="handler cm-sortable-handle"></span>
                    {/if}
                </td>
                {/if}

                <td width="{$href_col_width|default:( ($href_desc) ? "77%" : "28%")}" {if $href_desc_row_hint}data-th="{$href_desc_row_hint}"{else}data-th="&nbsp;"{/if}>
                    <div class="object-group-link-wrap">
                    {if !$non_editable}
                        <a {if $no_popup}href="{$href|fn_url}"{/if} title="{$text}" class="row-status {if !$main_link}cm-external-click{/if}{if $non_editable} no-underline{/if}{if $main_link} link{/if} {$link_meta}{if $is_promo}cm-promo-popup{/if} {if $text_wrap}row-status--text-wrap{/if}"{if !$non_editable && !$not_clickable} data-ca-external-click-id="opener_group{$id_prefix}{$id}"{/if}{if $main_link} {if !$is_promo}href="{$main_link|fn_url}{/if}"{/if}>{$text}</a>
                    {else}
                        <span class="unedited-element block {$link_meta}">{$text|default:__("view")}</span>
                    {/if}
                    {if $show_id}
                        <span class="muted"><small> #{$id}</small></span>
                    {/if}
                    {if $href_desc}<small>{$href_desc nofilter}</small>{/if}
                    {if $storefront_name}<div><small>{$storefront_name}</small></div>{/if}
                    {if $company_object}
                        {include file="views/companies/components/company_name.tpl" object=$company_object}
                    {/if}
                    </div>
                </td>
                <td width="{$details_col_width|default:( ($href_desc) ? "0%" : "50%")}" data-th="&nbsp;">
                    <span class="row-status object-group-details">{$details nofilter}</span>
                </td>

                {if $extra_data}
                    {$extra_data nofilter}
                {/if}

                <td width="10%" class="right nowrap" data-th="&nbsp;">

                    <div class="pull-right hidden-tools">
                        {capture name="items_tools"}
                        {if $tool_items}
                            {$tool_items nofilter}
                        {/if}
                            {if !$non_editable || $is_view_link}
                                {if $no_popup}
                                    <li>{btn type="list" text=$link_text|default:__("edit") href=$href}</li>
                                {else}
                                   <li>{include file="common/popupbox.tpl" id="group`$id_prefix``$id`" edit_onclick=$onclick text=$header_text act=$act|default:"edit" picker_meta=$picker_meta link_text=$link_text href=$href is_promo=$is_promo no_icon_link=true}</li>
                                {/if}
                            {/if}

                            {if !$non_editable}
                                {if $href_delete && !$skip_delete}
                                    {if $is_promo}
                                        {$class="cm-promo-popup"}
                                    {else}
                                        {$class="cm-delete-row"}
                                        {$href=$href_delete}
                                    {/if}
                                    <li>{btn type="text" text=__("delete") href=$href class="cm-confirm cm-ajax cm-ajax-force cm-ajax-full-render `$class`" data=["data-ca-target-id" => $delete_target_id, "data-ca-params" => $delete_data] method="POST"}</li>
                                {/if}
                            {/if}
                        {/capture}
                        {dropdown content=$smarty.capture.items_tools class="dropleft"}
                    </div>
                    {$links nofilter}
                </td>
                {if !$nostatus}
                    <td width="{$last_col_width|default:"12%"}" {if $status_row_hint}data-th="{$status_row_hint}"{else}data-th="&nbsp;"{/if}>
                        <div class="pull-right nowrap">
                            {if $non_editable == true || $is_promo}
                                {assign var="display" value="text"}
                            {/if}

                            {if $can_change_status}
                                {assign var="non_editable" value=false}
                                {assign var="display" value=""}
                            {/if}

                            {include file="common/select_popup.tpl" popup_additional_class="dropleft" id=$id status=$status hidden=$hidden object_id_name=$object_id_name table=$table hide_for_vendor=$hide_for_vendor display=$display non_editable=$non_editable update_controller=$update_controller st_result_ids=$st_result_ids}
                        </div>
                    </td>
                {/if}
            <!--{$row_id}--></tr>
    {if !$no_table}
        </tbody>
    </table>
</div>
{/if}
