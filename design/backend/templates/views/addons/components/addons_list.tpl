{$show_favorite = $show_favorite|default:true}
{$show_install_datetime = $show_install_datetime|default:true}
{$show_addon_status = $show_addon_status|default:true}
{$show_addon_tools = $show_addon_tools|default:true}
{$is_marketplace_addons = $is_marketplace_addons|default:false}

{if $is_marketplace_addons}
    {$show_favorite = false}
    {$show_install_datetime = false}
    {$show_version = false}
    {$show_install_datetime = false}
    {$show_supplier_link = false}
    {$show_addon_status = false}
    {$show_price = true}
    {$show_addon_tools = false}
    {$show_view_more_addons = true}
{/if}

{if $addons_list}
    <div class="table-responsive-wrapper addons-list" id="addon_table">
        <table class="table table-addons table-middle cm-filter-table ty-table--sorter
            table-responsive table-responsive-w-titles"
            data-ca-sortable="true"
            data-ca-sort-list="[[0, 1], [2, 0]]"
            data-ca-input-id="elm_addon"
            data-ca-clear-id="elm_addon_clear"
            data-ca-empty-id="elm_addon_no_items"
            data-ca-filter-table-is-logical-and="true"
        >
            <thead>
                <tr>
                    {if $show_favorite}
                    <th class="cm-tablesorter" data-ca-sortable-column="true" width="3%">
                        {include_ext file="common/icon.tpl"
                            class="icon-star-empty"
                            title=__("favorites")
                        }
                    </th>
                    {/if}
                    <th class="sorter-false" width="8%"></th>
                    <th class="cm-tablesorter" data-ca-sortable-column="true" width="40%">{__("name")}</th>
                    {if $show_install_datetime}
                    <th class="cm-tablesorter" data-ca-sortable-column="true" width="3%" title="{__("installed_date")}">
                        {include_ext file="common/icon.tpl"
                            class="icon-calendar muted"
                        }
                    </th>
                    {/if}
                    <th class="cm-tablesorter" data-ca-sortable-column="true" width="18%">{__("developer")}</th>
                    {if $show_price}
                    <th class="sorter-false" width="14%">{__("cscart_marketplace.price")}</th>
                    {/if}
                    {if $show_addon_tools}
                    <th class="sorter-false" width="7%"></th>
                    {/if}
                    <th class="{if $is_marketplace_addons}sorter-false{else}cm-tablesorter{/if} right" {if !$is_marketplace_addons}data-ca-sortable-column="true"{/if} width="20%">{if !$is_marketplace_addons}{__("status")}{/if}</th>
                </tr>
            </thead>
        {foreach $addons_list as $key => $a}

            {* Get full add-on info *}
            {include file="views/addons/components/addons/addon_full_info.tpl"
                addon=$a
                is_marketplace_addons=$is_marketplace_addons
            }

            {$addon_filter_source_suffix = ($a.is_core_addon) ? "built_in" : "third_party"}

            <tr class="cm-row-status-{$a.status|lower} cm-row-item
                filter_status_{$a.status} filter_source_{$addon_filter_source_suffix} {$additional_class}"
                id="addon_{$key}"
                data-supplier="{$a.supplier}"
                data-category="{$a.category}"
            >
                {if $show_favorite}
                <td>
                    {include file="views/addons/components/addons/addon_favorite.tpl"
                        result_ids="addon_table"
                        detailed=false
                    }
                </td>
                {/if}
                <td>
                    {include file="views/addons/components/addons/addon_icon.tpl"
                        addon=$a
                        href=true
                        show_description=true
                    }
                </td>
                <td>
                    {include file="views/addons/components/manage/addon_description.tpl"}
                </td>
                {if $show_install_datetime}
                <td>
                    {include file="views/addons/components/manage/addon_install_datetime.tpl"}
                </td>
                {/if}
                <td>
                    {include file="views/addons/components/addons/addon_supplier.tpl"}
                </td>
                {if $show_price}
                <td>
                    {include file="views/addons/components/marketplace/addon_price.tpl"}
                </td>
                {/if}
                {if $show_addon_status}
                <td class="nowrap row-status">
                    {include file="views/addons/components/addons/addon_status.tpl"}
                </td>
                {/if}
                <td class="nowrap right">
                    {if $is_marketplace_addons}
                        {include file="views/addons/components/marketplace/marketplace_addon_actions.tpl"}
                    {else}
                        {include file="views/addons/components/addons/addon_actions.tpl"}
                    {/if}
                </td>
            <!--addon_{$key}--></tr>
        {/foreach}
        </table>
    <!--addon_table--></div>

    {if $show_view_more_addons}
        <div id="view_more_addons_button">
            {include file="buttons/button.tpl"
                but_href=fn_url("helpdesk_connector.view_more_addons")
                but_text=__("view_more_addons")
                but_meta="btn btn-primary"
                but_role="action"
                but_target="_blank"
            }
        </div>
    {/if}
{else}
    <p id="elm_addon_no_items" class="no-items {if $addon_list}hidden{/if}">{__("no_data")}</p>
{/if}
