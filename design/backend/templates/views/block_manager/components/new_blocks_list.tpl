{foreach from=$block_types key="type" item="block"}
    {if $block.is_manageable}
        <div class="select-block {if $purpose === "wysiwyg"}cm-create-bm-block{else}cm-add-block bm-action-new-block{/if} {if $manage == "Y"}bm-manage{/if}"
             data-ca-block-type="{$block.type}"
             data-ca-block-name="{$block.name}"
        >
            <input type="hidden" name="block_data[type]" value="{$type}" />
            <input type="hidden" name="block_data[grid_id]" value="{$grid_id}" />

            <div class="select-block-box">
                <div class="bmicon-{$block.type|replace:"_":"-"}"></div>
            </div>

            <div class="select-block-description">
                <strong title="{$block.name}">{$block.name|truncate:20:"...":true|escape:html|replace:'...':'&hellip;' nofilter}</strong>
                <p>{$block.description}</p>
            </div>
        </div>
    {/if}
{/foreach}
