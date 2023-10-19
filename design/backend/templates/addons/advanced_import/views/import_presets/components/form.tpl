<form action="{""|fn_url}"
        method="post"
        name="manage_{$preffix}_import_presets_form"
        enctype="multipart/form-data"
        class="cm-skip-check-items import-preset cm-ajax cm-comet"
        data-ca-advanced-import-element="management_form"
        id="manage_{$preffix}_import_presets_form{$wrapper_extra_id}"
>
    <input type="hidden" name="object_type" value="{$object_type}"/>
    {$wrapper_content nofilter}
</form>