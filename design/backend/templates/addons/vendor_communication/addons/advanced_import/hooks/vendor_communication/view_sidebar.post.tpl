{if $object && $object.object_type == $smarty.const.VC_OBJECT_TYPE_IMPORT_PRESET}
<div class="sidebar-row">
    <h6>{__("vendor_communication.import_details")}</h6>
    <ul class="unstyled">
        <li>
            {if fn_check_permissions("import_presets", "update", "admin")}
                <a href="{"import_presets.update?preset_id=`$object.preset_id`"|fn_url}" title="{$object.preset}">
                    {$object.preset}
                </a>
            {else}
                {$object.preset}
            {/if}
        </li>
        <li>
            <span class="muted">
                {__("advanced_import.last_launch")}:
            </span>
            <span class="pull-right">
                {if $object.last_launch}
                    {$object.last_launch|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
                {else}
                    {__("advanced_import.never")}
                {/if}
            </span>
        </li>
    </ul>
</div>
{/if}