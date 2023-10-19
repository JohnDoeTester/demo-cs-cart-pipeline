{$allow_manage = fn_check_permissions("sync_data", "update", "admin", "GET", ["sync_provider_id" => $provider_id])}
{if $allow_manage}
    <tr>
        <td><a href="{"sync_data.update?sync_provider_id=`$provider_id`"|fn_url}">{$provider.name}</a></td>
        <td>{if !empty($last_sync_info.last_sync_timestamp)}{$last_sync_info.last_sync_timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}{else}{__("never")}{/if}</td>
        <td>{if !empty($last_sync_info.status)}{$last_sync_info.status}{else}{__("nothing_sign") nofilter}{/if}</td>
        <td>{if !empty($last_sync_info.log_file_url)}<a href="{$last_sync_info.log_file_url}">{$last_sync_info.log_file_name}</a>{else}{__("nothing_sign") nofilter}{/if}</td>
    </tr>
{/if}