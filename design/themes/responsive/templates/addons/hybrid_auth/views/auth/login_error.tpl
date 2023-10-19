<script data-no-defer>
    var target_window = opener || window;

    {if $redirect_url}
        var url = '{$redirect_url|escape:"javascript"}'.replace(/\&amp;/g, '&');
        target_window.location.href = url;
    {else}
        target_window.location.reload();
    {/if}

    if (opener) {
        window.close();
    }
</script>
