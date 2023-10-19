{strip}
{*
    $id
    $block_id
    $section
    $type
    $show_content
    $content
*}

{if $object_type === "custom_block"}
    {$show_descr = false}
    {$show_url = false}
    {$show_content = true}
{elseif $object_type === "menu_item"}
    {$show_descr = true}
    {$show_url = true}
    {$show_content = false}
{else}
    {$show_descr = true}
    {$show_url = false}
    {$show_content = false}
{/if}

<div class="block-manager-update-custom-block">
    <form action="{""|fn_url}"
        method="post"
        name="update_custom_block_form_{$id}"
        enctype="multipart/form-data"
        class="form-horizontal form-edit"
    >
        <input type="hidden" name="return_url" value="{$return_url|default:'index.index'}">
        <input type="hidden" name="block_data[block_id]" value="{$custom_block.block_id}">
        <input type="hidden" name="block_data[section]" value="{$custom_block.section}">
        <input type="hidden" name="block_data[type]" value="{$custom_block.type}">
        <input type="hidden" name="block_data[id_path]" value="{$custom_block.id_path}">

        {if $notifications}
            <div class="block-manager-update-custom-block__notifications">
                {foreach $notifications as $notification}
                    <div class="alert alert-block">
                        <p>{$notification}</p>
                    </div>
                {/foreach}
            </div>
        {/if}

        {if $show_descr}
            <div class="control-group">
                <label for="custom_block_descr_{$custom_block.block_id}" class="control-label cm-required">
                    {__("custom_blocks.name")}:
                </label>

                <div class="controls">
                    <input type="text"
                        id="custom_block_descr_{$custom_block.block_id}"
                        size="40"
                        class="input-large"
                        name="block_data[title]"
                        value="{$custom_block.title}"
                    />
                </div>
            </div>
        {/if}

        {if $show_url}
            <div class="control-group">
                <label for="custom_block_url_{$custom_block.block_id}" class="control-label {if $custom_block.menu_level !== "1"}cm-required{/if}">
                    {__("custom_blocks.url")}:
                </label>

                <div class="controls">
                    <input type="text"
                        id="custom_block_url_{$custom_block.block_id}"
                        size="40"
                        class="input-large"
                        name="block_data[href]"
                        value="{$custom_block.href}"
                    />
                     <p class="muted description">{if $custom_block.menu_level !== "1"}{__("custom_blocks.url.hint")}{else}{__("custom_blocks.first_menu_level_url.hint")}{/if}</p>
                </div>
            </div>

            <div class="control-group">
                <label for="custom_block_new_window_{$custom_block.block_id}" class="control-label">
                    {__("open_in_new_window")}:
                </label>

                <div class="controls">
                    <input type="hidden" name="block_data[new_window]" value="{"YesNo::NO"|enum}" />
                    <input type="checkbox" id="custom_block_new_window_{$custom_block.block_id}" name="block_data[new_window]" value="{"YesNo::YES"|enum}" {if $custom_block.new_window === "YesNo::YES"|enum}checked="checked"{/if} />
                </div>
            </div>
        {/if}

        {if $show_content}
            <div class="control-group">
                <label for="custom_block_content_{$custom_block.block_id}" class="control-label cm-required">
                    {__("custom_blocks.content")}:
                </label>

                <div class="controls">
                    <textarea id="custom_block_content_{$custom_block.block_id}"
                        name="block_data[content]"
                        cols="55"
                        rows="8"
                        class="cm-wysiwyg input-large"
                    >{$custom_block.content nofilter}</textarea>
                </div>
            </div>
        {/if}

        <div class="buttons-container">
            {include file="buttons/button.tpl"
                but_text=($custom_block.block_id) ? __("save") : __("create")
                but_role="submit-button"
                but_name=($object_type === "menu_item") ? "dispatch[custom_menu.update_block]" : "dispatch[index.update_block]"
            }
        </div>
    </form>
</div>
{/strip}