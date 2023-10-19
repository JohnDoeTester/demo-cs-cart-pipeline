{if !$simple}
    <button type="button"
            class="btn cm-opacity {if $only_delete == "Y"} hidden{/if}"
            name="remove"
            id="{$item_id}"
            title="{__("remove")}"
    >
        {include_ext file="common/icon.tpl" class="icon-trash"}
    </button>
{/if}

<button type="button"
        name="remove_hidden"
        id="{$item_id}"
        class="btn {if !$simple && $only_delete != "Y"} hidden{/if}{if $but_class} {$but_class}{/if}"
        title="{__("remove")}"
        {if $but_onclick} onclick="{$but_onclick}"{/if}
>
    {include_ext file="common/icon.tpl" class="icon-trash"}
</button>
