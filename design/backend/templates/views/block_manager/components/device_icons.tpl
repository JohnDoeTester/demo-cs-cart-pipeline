{$devices = $item.availability|default:[]}
{$all_devices = $devices|array_filter == $devices}

<div class="device-specific-block__devices {$wrapper_class}">
    {foreach $devices as $device => $is_available}
        <div class="device-specific-block__devices__device device-specific-block__devices__device--{$device} icon-{$device} {if $all_devices || !$is_available}hidden{/if}"></div>
    {/foreach}
</div>
