{*
    $id
    $message_title
    $name
    $autofocus
*}

<div class="ty-control-group">
    {if $message_title}
        <label class="hidden
            {if $required}
                cm-required cm-trim
            {/if}"
            for="{$id}"
        >
            {$message_title nofilter}
        </label>
    {/if}

    <textarea id="{$id}"
        name="{$name}"
        class="ty-input-textarea ty-input-textarea--limit ty-input-text-full"
        {if $message_title}
            placeholder="{$message_title nofilter}"
            title="{$message_title nofilter}"
        {/if}
    ></textarea>
</div>
