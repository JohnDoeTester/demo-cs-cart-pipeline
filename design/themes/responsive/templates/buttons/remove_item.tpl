{strip}
{if !$simple}
<a name="remove" id="{$item_id}" alt="{__("remove_this_item")}" title="{__("remove_this_item")}" class="button-icon ty-icon-remove ty-icon-remove-disable{if $only_delete === "Y"} hidden{/if}" >{include_ext file="common/icon.tpl" class="ty-icon-cancel-circle"}</a>
{/if}
<a name="remove_hidden" id="{$item_id}" alt="{__("remove_this_item")}" title="{__("remove_this_item")}"{if $but_onclick} onclick="{$but_onclick}"{/if} class="button-icon ty-icon-remove {if !$simple && $only_delete !== "Y"} hidden{/if}{if $but_class} {$but_class}{/if}" >{include_ext file="common/icon.tpl" class="ty-icon-cancel-circle"}</a>
{/strip}
