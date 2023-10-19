{$devices = $item.availability|default:[]}
{foreach $devices as $device => $is_available}
    data-ca-device-availability-{$device}="{if $is_available}true{else}false{/if}"
{/foreach}
