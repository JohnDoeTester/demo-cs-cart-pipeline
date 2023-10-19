{if $but_text}
    {$_but_text = $but_text}
{else}
    {$_but_text = __("recalculate")}
{/if}

{include file="buttons/button.tpl" 
         but_id=$but_id
         but_text=$_but_text
         but_onclick=$but_onclick
         but_href=$but_href
         but_target=$but_target
         but_meta="ty-btn__tertiary $but_meta"
         but_role="action"
}