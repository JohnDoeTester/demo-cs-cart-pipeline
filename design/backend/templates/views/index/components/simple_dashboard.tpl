{hook name="index:simple_dashboard"}
    <div class="simple-dashboard"
        {if $runtime.customization_mode.block_manager && $location_data.is_frontend_editing_allowed}
            data-ca-block-manager="main"
            data-ca-block-manager-has-layout="0"
        {/if}
    id="simple_dashboard">
        {hook name="index:simple_dashboard_content"}
            {include file="views/index/components/custom_blocks_section/custom_blocks_section.tpl"
                custom_blocks_section=$custom_blocks_data.{"CustomBlockSections::TOP_PANEL"|enum}
                section="CustomBlockSections::TOP_PANEL"|enum
                type="CustomBlockTypes::BLOCK"|enum
            }
            {include file="views/index/components/todo_section/todo_section.tpl"}
            {include file="views/index/components/custom_blocks_section/custom_blocks_section.tpl"
                custom_blocks_section=$custom_blocks_data.{"CustomBlockSections::HEADER"|enum}
                section="CustomBlockSections::HEADER"|enum
                type="CustomBlockTypes::BLOCK"|enum
            }
            {include file="views/index/components/custom_blocks_section/custom_blocks_section.tpl"
                custom_blocks_section=$custom_blocks_data.{"CustomBlockSections::ONBOARDING"|enum}
                section="CustomBlockSections::ONBOARDING"|enum
                type="CustomBlockTypes::BLOCK"|enum
            }
            {include file="views/index/components/custom_blocks_section/custom_blocks_section.tpl"
                custom_blocks_section=$custom_blocks_data.{"CustomBlockSections::CONTENT"|enum}
                section="CustomBlockSections::CONTENT"|enum
                type="CustomBlockTypes::BLOCK"|enum
            }
            {include file="views/index/components/analytics_section/analytics_section.tpl"}
            {include file="views/index/components/custom_blocks_section/custom_blocks_section.tpl"
                custom_blocks_section=$custom_blocks_data.{"CustomBlockSections::FOOTER"|enum}
                section="CustomBlockSections::FOOTER"|enum
                type="CustomBlockTypes::BLOCK"|enum
            }
        {/hook}
    <!--simple_dashboard--></div>
{/hook}

{capture name="buttons"}
    {include file="common/daterange_picker.tpl"
        id="dashboard_date_picker"
        extra_class="nav__actions-dashboard-date-picker pull-right"
        data_url="index.index?storefront_id={$storefront_id}"|fn_url
        data_event="ce.dashboard.daterange_picker"
        result_ids="simple_dashboard"
        start_date=$time_from
        end_date=$time_to
    }
    {script src="js/tygh/backend/dashboard/daterange_picker.js"}
{/capture}
