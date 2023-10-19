{strip}
{*
    $show_custom_blocks_section
    $custom_blocks_section
    $section
    $type
    $custom_blocks_section_class
    $create_block_button_text
    $create_block_button_class
    $return_url

    $custom_blocks_card
*}

{$show_custom_blocks_section = $show_custom_blocks_section|default:true}

{$hasLayout = false}
{if $custom_blocks_section.section === "CustomBlockSections::TOP_PANEL"|enum
    || $custom_blocks_section.section === "CustomBlockSections::HEADER"|enum
    || $custom_blocks_section.section === "CustomBlockSections::ONBOARDING"|enum
}
    {$custom_blocks_section_class = "custom-blocks-section--fixed"}
    {$custom_blocks_section.is_fixed = true}
{/if}

{if $custom_blocks_section.section === "CustomBlockSections::ONBOARDING"|enum}
    {$custom_blocks_section.title = __("custom_blocks.sections.onboarding_title")}
    {$custom_blocks_section.is_editable = true}
{/if}

{$return_url = $config.current_url|escape:url}
{$section = $section|default:{"CustomBlockSections::ONBOARDING"|enum}}
{$type = $type|default:{"CustomBlockTypes::BLOCK"|enum}}

{if $custom_blocks_section.content || $custom_blocks_section.title}
    {$create_block_button_text = __("custom_blocks.create_block")}
    {$create_block_button_class = "custom-blocks-section__create custom-blocks-section__create--center"}
{else}
    {$create_block_button_text = __("custom_blocks.use_this_space")}
    {$create_block_button_class = "custom-blocks-section__create"}
{/if}

{if $custom_blocks_section.is_fixed}
    {$create_block_button_class = "`$create_block_button_class` custom-blocks-section__create--fixed"}
{/if}

<section class="custom-blocks-section {$custom_blocks_section_class}" id="{$custom_blocks_section.id}">
    {if $show_custom_blocks_section && ($custom_blocks_section.content || $runtime.customization_mode.block_manager)}
        <div class="custom-blocks-section__inner" {""}
            {if $runtime.customization_mode.block_manager}
                data-ca-block-manager-grid-id="{$custom_blocks_section.id}"
            {/if}
        >
            {if $custom_blocks_section.title}
                <h2 class="custom-blocks-section__title">
                    {$custom_blocks_section.title}
                </h2>
            {/if}
            <div class="custom-blocks-section__content">
                <div class="custom-blocks-section__column"
                    {if $runtime.customization_mode.block_manager}
                        data-ca-block-manager-table="custom_blocks"
                        data-ca-block-manager-id-name="block_id"
                        data-ca-block-manager-blocks-place="true"
                    {/if}
                >
                    {foreach $custom_blocks_section.content as $custom_blocks_card}
                        {include file="views/index/components/custom_blocks_section/custom_blocks_card.tpl"
                            custom_blocks_card=$custom_blocks_card
                            assign="custom_blocks_card_content"
                        }

                        {include file="views/block_manager/frontend_render/block.tpl"
                            content=$custom_blocks_card_content
                            block=$custom_blocks_card
                            custom_blocks_section=$custom_blocks_section
                            location_data=$location_data
                            snapping_id=$custom_blocks_card.id
                            object_type="custom_block"
                            parent_grid=[
                                location_id => $custom_blocks_card.section
                            ]
                            prefix=$custom_blocks_section.id
                            popup_title=__("custom_blocks.edit_block")
                            show_delete=true
                            is_popup=true
                        }
                    {/foreach}
                </div>
            </div>

            {if $runtime.customization_mode.block_manager}
                <div class="custom-blocks-section__footer">
                    {include file="common/popupbox.tpl"
                        id="`$custom_blocks_section.id`_create_block"
                        text=__("custom_blocks.create_block")
                        link_text=$create_block_button_text
                        link_class=$create_block_button_class
                        act="link"
                        href="block_manager.update_custom_block?object_type=custom_block&block_id=0&section=`$section`&type=`$type`&return_url=$return_url"
                    }
                </div>
            {/if}
        </div>
    {/if}
<!--{$custom_blocks_section.id}--></section>
{/strip}