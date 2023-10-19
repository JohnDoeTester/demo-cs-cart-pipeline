<div id="block_type_list">
    <div id="content_block_type_list_{$extra_id}">
        {foreach $block_types as $type => $block}
            {capture name="block_edit_link"}
                <div class="select-block-box">
                    {include_ext file="common/icon.tpl" class="bmicon-`$block.type|replace:'_':'-'`"}
                </div>
                <div class="select-block-description">
                    <strong title="{$block.name}">{$block.name|truncate:20:"...":true|escape:html|replace:'...':'&hellip;' nofilter}</strong>
                    <p>{$block.description}</p>
                </div>
            {/capture}

            {if $block.is_manageable}
                <div class="select-block">
                        {include file="common/popupbox.tpl"
                        id="block_properties_{$block.type}"
                        title_start=__("add_block")
                        title_end=$block.name
                        act="link"
                        href="block_manager.update_block?block_data[type]={$type}&r_url={$smarty.request.r_url|escape:url}"
                        opener_ajax_class="cm-ajax cm-ajax-force"
                        content=""
                        link_text={$smarty.capture.block_edit_link nofilter}
                        }
                </div>
            {/if}
        {/foreach}
        <!--content_create_new_blocks_{$extra_id}--></div>
    <!--add_new_block{$extra_id}--></div>
