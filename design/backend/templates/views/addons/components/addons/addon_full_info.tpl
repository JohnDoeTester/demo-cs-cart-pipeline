{* $addon_full_version_info *}
{* $addon_full_description *}

{$ADDON_NAME_LIMIT = 50 scope=parent}

{if $addon.status === "ObjectStatuses::ACTIVE"|enum}
    {$status_description = __("active")}
{elseif $addon.status === "ObjectStatuses::DISABLED"|enum}
    {$status_description = __("disabled")}
{elseif $addon.status === "ObjectStatuses::NEW_OBJECT"|enum}
    {$status_description = __("not_installed")}
{/if}
{$installed_version = $addon.version}
{$latest_version = $addon.latest_upgrade_version|default: __("na")}
{$actual_version = $addon.actual_version|default: __("na")}
{$verified_developer = ($a.identified || $a.is_core_addon) ? " ({__("verified")})" : ""}

{* Truncate add-on name *}
{$a.is_long_name = ($a.name|strip_tags|count_characters:true > $ADDON_NAME_LIMIT) scope=parent}
{$a.short_name = $a.name|truncate:$ADDON_NAME_LIMIT:"...":true|default:$key scope=parent}

{* Full add-on version info *}
{* ------------------------------------------------------------------------- *}
{if $addon.is_core_addon || !$addon.actual_version}
    {$addon_full_version_info = "{__("installed_version")}:
`$installed_version`" scope=parent}

{elseif $actual_version === $latest_version}
    {$addon_full_version_info = "{__("addons.latest_version")}:
`$actual_version`

{__("installed_version")}:
`$installed_version`" scope=parent}

{else}
    {$addon_full_version_info = "{__("addons.latest_version")}:
`$actual_version`

{__("addons.latest_available_for_installation_version")}:
`$latest_version`

{__("installed_version")}:
`$installed_version`" scope=parent}

{/if}

{* Format date *}
{$install_datetime_format = $a.install_datetime|date_format:"{$settings.Appearance.date_format}, {$settings.Appearance.time_format}"}

{* Full add-on description info *}
{* ------------------------------------------------------------------------- *}
{if $is_marketplace_addons}
{$addon_full_description = "`$addon.name`

`$addon.description|strip_tags:false`

{__("developer")}: `$a.supplier``$verified_developer`
{__("addon_id")}: `$addon.addon`" scope=parent}
{else}  
{$addon_full_description = "`$addon.name`

`$addon.description|strip_tags:false`

{__("version")}: `$addon.version|default:0.1`
{__("installed_date")}: `$install_datetime_format`
{__("developer")}: `$a.supplier``$verified_developer`
{__("addon_id")}: `$addon.addon`
{__("status")}: `$status_description`" scope=parent}
{/if}

{* Full install datetime info *}
{* ------------------------------------------------------------------------- *}
{$install_datetime_full_info = "{__("installed_date")}:
`$install_datetime_format`" scope=parent}
