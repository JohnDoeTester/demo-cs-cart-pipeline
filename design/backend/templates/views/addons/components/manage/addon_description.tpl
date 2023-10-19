{$show_version = $show_version|default:true}
{$show_install_datetime = $show_install_datetime|default:true}

{if $is_marketplace_addons}
    {$href = $a.addon_marketplace_page}
    {$is_open_new_tab = true}
{else}
    {$href = "addons.update?addon={$a.addon}"|fn_url}
    {if fn_allowed_for("MULTIVENDOR") && $selected_storefront_id}
        {$href = $href|fn_link_attach:"storefront_id={$selected_storefront_id}"}
    {/if}
{/if}

{* Get addon license required text *}
{include file="views/addons/components/addons/addon_license_required.tpl"}

<div class="addons-addon-description">
    <div>
        <a href="{$href}"
           class="row-status addons-addon-description__name addons-addon-description__name--{$a.status|lower} nowrap-responsive"
           title="{$addon_full_description}"
           {if $is_open_new_tab}
               target="_blank"
           {/if}
        >
            {$a.short_name}
        </a>

        {if $a.recently_installed}
            {include_ext file="common/icon.tpl"
                class="icon-circle addons-addon-description__new-addon addons-addon-description__new-addon--`$a.status|lower`"
                title=__("new_addon")
            }
        {/if}
    </div>
    <div class="addons-addon-description__description">
        <small class="muted addons-addon-description__description-small">
            {strip_tags($a.description,"<a>") nofilter}
        </small>
    </div>
    {if $show_version || $show_install_datetime}
        <div>
            <small class="muted" title="{$addon_full_version_info}">
                {$a.version|default:0.1}
            </small>
            <small class="muted">•</small>
            {if $a.install_datetime}
                <small class="muted" title="{$install_datetime_full_info}">
                    {$a.install_datetime|date_format:"`$settings.Appearance.date_format`"}
                </small>
            {else}
                <small class="muted">—</small>
            {/if}

        </div>
    {/if}

    {* Hidden text for search *}
    <div class="hidden">
        {if $a.is_long_name}
            {$a.name nofilter}
        {/if}
        {$a.addon nofilter}
    </div>
</div>
