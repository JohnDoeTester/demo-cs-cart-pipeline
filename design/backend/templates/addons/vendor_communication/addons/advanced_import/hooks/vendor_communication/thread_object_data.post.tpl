{if $object_type == $smarty.const.VC_OBJECT_TYPE_IMPORT_PRESET}
    {if $new_thread}
        <input type="hidden" name="thread[subject]" id="thread_message_subject_{$object_id}" value="{__("import")} #{$object_id}"/>
    {/if}
    {if fn_check_permissions("import_presets", "update", "admin")}
        <a href="{"import_presets.update?preset_id=`$object_id`"|fn_url}"
           class="post-object" title="{__("import")} #{$object_id}">
            {__("import")} #{$object_id}
        </a>
    {else}
        <span class="post-object">{__("import")} #{$object_id}</span>
    {/if}
{/if}