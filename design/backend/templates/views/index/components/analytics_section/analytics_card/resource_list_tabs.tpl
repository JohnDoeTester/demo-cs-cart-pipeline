{if $resource_list_tabs && $resource_list_tabs.content}{strip}
    {*
        $analytics_card
        $resource_list_tabs
        $tab
    *}
    <div class="analytics-card-resource-list-tabs"
        data-ca-analytics-card="resourceList"
        data-ca-analytics-card-id="{$analytics_card.id}_{$resource_list_tabs.id}"
    >
        <div class="analytics-card-resource-list-tabs__tabs"
            data-ca-analytics-card="resourceListTabs"
            data-ca-analytics-card-id="{$analytics_card.id}_{$resource_list_tabs.id}"
        >
            {capture name="tabsbox"}
                {foreach $resource_list_tabs.content as $tab}
                    {$resource_list_tabs.navigation.tabs["`$analytics_card.id`_`$resource_list_tabs.id`_`$tab.id`"] = [
                        title => $tab.title,
                        js => true
                    ]}
                    {$tab.title = false}
                    <div class="analytics-card-analytics-tabs__tab-content"  id="content_{$analytics_card.id}_{$resource_list_tabs.id}_{$tab.id}">
                        {include file="views/index/components/analytics_section/analytics_card/resource_list.tpl"
                            resource_list=$tab
                        }
                    <!--content_{$analytics_card.id}_{$resource_list_tabs.id}_{$tab.id}--></div>
                {/foreach}
            {/capture}
            {include file="common/tabsbox.tpl"
                content=$smarty.capture.tabsbox
                active_tab=$smarty.request.selected_section
                track=true
                navigation=$resource_list_tabs.navigation
                meta_tabs="analytics-card-resource-list-tabs__tabs-navigation"
            }
        </div>
    </div>
{/strip}{/if}