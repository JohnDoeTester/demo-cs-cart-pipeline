{if $addons.backend_google_auth.status == "A"}
<div class="control-group setting-wide">
    <div class="controls">
        {include file="buttons/button.tpl" but_role="submit" but_meta="cm-new-window" but_target="_blank" but_name="dispatch[addons.update.backend_google_auth_check]" but_text=__("backend_google_auth.settings.save_and_check_btn")}
    </div>
</div>
{/if}