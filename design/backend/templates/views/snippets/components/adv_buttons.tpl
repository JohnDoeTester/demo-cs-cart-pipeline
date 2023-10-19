{if fn_check_permissions("snippets", "update", "admin", "POST")}
    {$return_url_escape = $return_url|escape:"url"}

    {include file="common/popupbox.tpl"
        method="POST"
        id="add_snippet"
        text="{__("add_snippet")}"
        link_text=$link_text|default:""
        act="general"
        icon="icon-plus"
        href="snippets.update?snippet_id=0&return_url={$return_url_escape}&current_result_ids={$result_ids}&type={$type}&addon={$addon}"
    }
{/if}