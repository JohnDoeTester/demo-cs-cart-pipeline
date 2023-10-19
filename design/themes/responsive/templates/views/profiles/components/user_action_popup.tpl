<div id="{$action}_dialog_{$id}" title="{$title}">
    <form action="{""|fn_url}" method="POST">
        {$description nofilter}

        <input type="hidden" name="redirect_url" value="{$config.current_url}"/>

        <div class="ty-control-group">
            <label for="{$action}_comment" class="ty-control-group__title">{__("type_comments_here")}</label>
            <textarea id="{$action}_comment" name="anonymization_request[comment]" class="ty-input-textarea cm-focus" autofocus rows="5" cols="100"></textarea>
        </div>

        <div class="buttons-container">
            {include file="buttons/button.tpl" but_text=__("confirm") but_meta="ty-btn__secondary cm-skip-validation" but_role="submit" but_name="dispatch[profiles.anonymization_request]"}
        </div>
    </form>
</div>
