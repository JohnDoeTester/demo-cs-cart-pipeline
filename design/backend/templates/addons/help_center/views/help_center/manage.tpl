{capture name="mainbox"}
    {script src="js/tygh/tabs.js"}

    {if $dispatch_id}
        {$default_dispatch = $dispatch_id}
    {elseif $current_dispatch === "help_center.manage"}
        {$default_dispatch = ""}
    {else}
        {$default_dispatch = $current_dispatch}
    {/if}
    {$suffix = ($in_popup) ? "popup" : ""}
    {$help_center_request = [
        version         => $product_version,
        edition         => $product_edition,
        lang_code       => $smarty.const.CART_LANGUAGE,
        dispatch        => $default_dispatch,
        product_build   => $smarty.const.PRODUCT_BUILD,
        store_domain    => $config.current_host
    ]|@json_encode}

    {capture name="product_release_info" assign=product_release_info}
        {include file="common/product_release_info.tpl"
            is_version_shown=false
            is_time_shown=false
        }
        {include file="common/product_release_info.tpl"
            is_product_shown=false
            is_time_shown=false
        }
    {/capture}

    <section data-ca-help-center="main"
        data-ca-help-center-server-url="{$help_center_server_url}"
        data-ca-help-center-request="{$help_center_request}"
        data-ca-help-center-default-dispatch="{$default_dispatch}"
        data-ca-help-center-active-dispatch="{$default_dispatch}"
        data-ca-help-center-is-inited="0"
        data-ca-help-center-relevant-chapter="info"
        data-ca-help-center-no-data-relevant-text="{__("help_center.no_data_relevant")|escape}"
        data-ca-help-center-no-data-text="{__("help_center.no_data")|escape}"
        data-ca-help-center-product-release-info="{$product_release_info|strip_tags:false|strip}"
        data-ca-help-center-timestamp-last-view="{$help_center_timestamp_last_view|default:0}"
        data-ca-help-center-customer-last-update="{$help_center_customer_care_data_last_update|default:0}"
        {if $in_popup}
            data-ca-help-center-suffix="{$suffix}"
            data-ca-help-center-in-popup="1"
        {/if}
        class="help-center {if $in_popup}help-center--popup{/if}"
        id="manage_help_center_{$suffix}"
    >
        <form action="{""|fn_url}" method="post" name="manage_help_center_form" class="help-center__form" id="manage_help_center_form_{$suffix}">
            <div class="help-center__wrapper">
                <aside class="sidebar-row help-center__navs" data-ca-help-center="navs">
                    <ul class="help-center__navs-content" data-ca-help-center="navsContentTarget">{* Navs slot *}</ul>
                </aside>

                <div class="help-center__content">
                    <div class="help-center__sections"
                        data-ca-help-center-loading-text="{__("help_center.loading")}"
                        data-ca-help-center="sectionsTarget">{* Sections slot *}</div>
                </div>
            </div>
        </form>

        {* Script templates *}
        {include file="addons/help_center/templates/help_center_nav_chapter.tpl"}
        {include file="addons/help_center/templates/help_center_nav_item.tpl"}
        {include file="addons/help_center/templates/help_center_block.tpl"}
        {include file="addons/help_center/templates/help_center_section.tpl"}

        {* Sections injection *}
        {include file="addons/help_center/component/help_center_sections_injection.tpl"
            sections=[
                $help_center_customer_care_data.tickets
            ]
            ask_data=$help_center_customer_care_data
            ask_data_last_update=$help_center_customer_care_data_last_update
            help_center_last_view=$help_center_timestamp_last_view
        }
    </section>
{/capture}

{if $in_popup}
    {$smarty.capture.mainbox nofilter}
{else}
    {include file="common/mainbox.tpl"
        title=__("help_center.growth_center")
        content=$smarty.capture.mainbox
        box_id="page_content_help_center"
    }
{/if}
