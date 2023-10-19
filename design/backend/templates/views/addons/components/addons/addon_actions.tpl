{$target_id="addons_list,header_navbar,header_subnav,addons_counter,elm_developer_pages,elm_all_dev_pages"}
{$c_url = $config.current_url}

{if $runtime.company_id}
    {$hide_for_vendor = true}
{/if}

{if $a.status === "ObjectStatuses::NEW_OBJECT"|enum}
    {$status_text = __("not_installed")}
{elseif $a.status === "ObjectStatuses::DISABLED"|enum}
    {$status_text = __("disabled")}
{elseif $a.status === "ObjectStatuses::ACTIVE"|enum}
    {$status_text = __("active")}
{/if}

{$action_btn_text = ($a.main_page) ? "<span class=\"caret\"></span>" : "{__("open_action")} <span class=\"caret\"></span>"}

{* Sorting by Status column *}
{if $a.status === "ObjectStatuses::ACTIVE"|enum && $a.menu_items}
    {$position = "10"}
{elseif $a.status === "ObjectStatuses::ACTIVE"|enum}
    {$position = "20"}
{elseif $a.status === "ObjectStatuses::DISABLED"|enum}
    {$position = "30"}
{elseif $a.status === "ObjectStatuses::NEW_OBJECT"|enum}
    {$position = "40"}
{/if}

{* Get addon license required text *}
{include file="views/addons/components/addons/addon_license_required.tpl"}

<div>
    <div class="hidden">
        {$position}{$a.short_name}
    </div>

    {if !$a.snapshot_correct}
        <a href={$license_required.href}
            class="btn cm-post cm-dialog-opener cm-dialog-auto-size"
            data-ca-target-id={$license_required.target_id}
            data-ca-dialog-title="{$license_required.promo_popup_title}"
        >
            {if $a.status === "ObjectStatuses::DISABLED"|enum}
                {__("addons.activate")}
            {else}
                {__("addons.install")}
            {/if}
        </a>
    {elseif $a.status === "ObjectStatuses::NEW_OBJECT"|enum}
        {if !$hide_for_vendor}
            <div>
                <a href="{"addons.install?addon=`$key`&return_url=`$c_url|escape:url`"|fn_url}"
                    class="btn cm-post cm-ajax cm-ajax-full-render"
                    data-ca-target-id={$target_id}
                >
                    {__("addons.install")}
                </a>
            </div>
            {/if}
    {elseif $a.status === "ObjectStatuses::DISABLED"|enum}
        {if $a.is_disabled === true || $is_storefront_admin}
            <span class="shift-right"> {__("addons.disabled")} </span>
        {else}
            <a href="{"addons.update_status?id=`$key`&status={"ObjectStatuses::ACTIVE"|enum}&redirect_url=`$c_url|escape:url`"|fn_url}"
               class="btn cm-ajax cm-post cm-ajax-full-render"
               data-ca-target-id="{$target_id}"
               data-ca-event="ce.update_object_status_callback"
            >
                {__("addons.activate")}
            </a>
        {/if}

    {elseif $a.status === "ObjectStatuses::ACTIVE"|enum && $a.menu_items}
        {if $a.upgrade_available}
            <span class="shift-right muted" title="{__("active")}. {__("addons.upgrade_available")}">
                {__("addons.upgrade_available")}
            </span>
        {/if}
        <div class="nowrap inline-block-basic">
            <div class="btn-group dropleft">
                {if $a.main_page}
                    <a href="{$a.main_page|fn_url}" class="btn">{__("open_action")}</a>
                {/if}
                <button class="btn dropdown-toggle" data-toggle="dropdown">
                    {$action_btn_text nofilter}
                </button>
                <ul class="dropdown-menu">
                    {foreach $a.menu_items as $href => $menu_item}
                        <li>
                        <a href="{fn_url($href)}">
                            {($menu_item.title) ? $menu_item.title : __($menu_item.id)}
                            {if $menu_item.parents}
                                {strip}
                                    (
                                    {foreach $menu_item.parents as $parent name="addon_menu_item_parents"}
                                        {($parent.title) ? $parent.title : __($parent.id)}
                                        {if !$smarty.foreach.addon_menu_item_parents.last} / {/if}
                                    {/foreach}
                                    )
                                {/strip}
                            {/if}
                        </a>
                        </li>
                    {/foreach}
                </ul>
            </div>
        </div>

    {else}

        {if $a.upgrade_available}
            <span class="shift-right muted" title="{__("active")}. {__("addons.upgrade_available")}">
                {__("addons.upgrade_available")}
            </span>
        {else}
            <span class="shift-right">{__("active")}</span>
        {/if}

    {/if}
</div>
