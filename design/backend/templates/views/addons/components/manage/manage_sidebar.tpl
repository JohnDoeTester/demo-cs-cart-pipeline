{hook name="addons:manage_sidebar"}
    {include file="views/addons/components/manage/addon_name_search.tpl"}
    {include file="common/saved_search.tpl" dispatch="addons.manage" view_type="addons" allow_new_search=false}
    {if $category_tree}
        <div class="sidebar-row">
            <h6>{__("categories")}</h6>
            <div class="nested-tree">
                {include file="views/addons/components/addon_categories_tree.tpl"
                    show_all=false
                    categories_tree=$category_tree
                    direction="right"
                }
            </div>
        </div>
    {/if}
{include file="views/addons/components/manage/addons_search_form.tpl" dispatch="addons.manage"}

{* Hook saved for backward compatibility *}
{hook name="addons:manage_sidebar_marketplace"}
{/hook}
{/hook}