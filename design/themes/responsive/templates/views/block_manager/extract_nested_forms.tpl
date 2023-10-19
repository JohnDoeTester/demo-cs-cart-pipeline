{*
    Import
    ---
    $wrapper
    $content

    Global
    ---
    $form
    $start
    $form_content
    $form_tag
    $form_id
    $smarty.capture.wrapper

    Insert
    ---
    $form_tag

    Export
    ---
    $content
*}

{capture name="wrapper"}
    {include file=$wrapper content="&nbsp;"}
{/capture}

{if $smarty.capture.wrapper|strpos:"<form" && $content|strpos:"<form"}

    {foreach "<form"|explode:$content as $form}
        {if $form@first}
            {continue}
        {/if}

        {* Get the content, tag, and ID of the form *}
        {$form = "<form`$form`"}
        {$start = $form|strpos:"<form"}
        {$form_content = $form|substr:$start:($form|strpos:"</form" + 7 - $start)}
        {$form_tag = "`$form_content|substr:0:($form_content|strpos:">" + 1)`</form>"}
        {$form_id = $form_tag|substr:($form_content|strpos:"id=\"" + 4)}
        {$form_id = $form_id|substr:0:($form_id|strpos:"\"")}

        {* Link all fields to the form *}
        {$content = $content|replace:$form_content:($form_content|replace:
            "<form":"<x-form"|replace:
            "</form":"</x-form"|replace:
            "id=\"`$form_id`\">":"id=\"`$form_id`_base\">"|replace:
            "<input":"<input form=\"`$form_id`\""|replace:
            "<select":"<select form=\"`$form_id`\""|replace:
            "<textarea":"<textarea form=\"`$form_id`\""|replace:
            "<button":"<button form=\"`$form_id`\""
        )}

        {* Insert the form outside of the wrapper form *}
        {$form_tag|replace:" class=\"":" class=\"hidden cm-outside-inputs " nofilter}

        {* Export *}
        {$content = $content scope=parent}
    {/foreach}
{/if}
