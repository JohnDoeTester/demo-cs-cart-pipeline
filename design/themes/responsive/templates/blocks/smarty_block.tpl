{* smarty_block.tpl was deprecated since 4.16.1, use safe_smarty_block.tpl instead *}
{strip}

{if !$no_wrap}
    <div class="ty-wysiwyg-content" {live_edit name="block:content:{$block.block_id}" phrase=$content need_render=true} data-ca-live-editor-object-id="{$block.object_id}" data-ca-live-editor-object-type="{$block.object_type}">
{/if}

{eval_string var=$content}

{if !$no_wrap}
    </div>
{/if}

{/strip}
