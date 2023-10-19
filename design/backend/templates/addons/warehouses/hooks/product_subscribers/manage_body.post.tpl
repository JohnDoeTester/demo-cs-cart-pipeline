{if $destinations}
    <td data-th="{__("destination")}"><input type="hidden" name="subscribers[{$s.subscriber_id}][destination]" value="{$s.destination}" />
        <a href="{"destinations.update&destination_id={$s.destination_id}"|fn_url}">{$s.destination}</a>
    </td>
{/if}