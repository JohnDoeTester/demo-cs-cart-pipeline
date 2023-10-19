{foreach from=$unique_blocks item="block"}
    {if $block_types[$block.type]}
        <div class="select-block {if $purpose === "wysiwyg"}cm-select-bm-block{else}cm-add-block bm-action-existing-block{/if} {if $manage == "Y"}bm-manage{/if} {if $block.single_for_location}bm-block-single-for-location{/if}"
             data-ca-block-uid="{$block.unique_id}"
             data-ca-block-name="{$block.name}"
        >
            <input type="hidden" name="block_id" value="{$block.block_id}" />
            <input type="hidden" name="grid_id" value="{$grid_id|default:"0"}" />
            <input type="hidden" name="type" value="{$block.type}" />
            {if $purpose !== "wysiwyg"}
                <a class="icon-remove-circle cm-tooltip cm-remove-block" title="{__("delete_block")}"></a>
            {/if}
            <div class="select-block-box">
                <div class="bmicon-{$block.type|replace:"_":"-"}"></div>
            </div>
            <div class="select-block-description">
                <strong title="{$block.name}">{$block.name|truncate:20:"...":true|escape:html|replace:'...':'&hellip;' nofilter}</strong>
                <p>{$block_types[$block.type].description}</p>
            </div>
        </div>
    {/if}
{/foreach}
