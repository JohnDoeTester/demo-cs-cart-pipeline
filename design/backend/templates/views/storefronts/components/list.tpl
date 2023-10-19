{*
\Tygh\Storefront\Storefront[] $storefronts            Storefronts list
array                         $search                 Storefronts search parameters
string                        $sort_active_icon_class Icon class of active sort
string                        $sort_dummy_icon_class  Icon class of inactive sort
string                        $sort_url               URL of the page for sortings
string                        $return_url             URL to redirect to after storefront status is changed or storefront is deleted
bool                          $is_readonly            Whether read-only list of storefronts must be displayed
string                        $select_mode            Storefront selection mode
bool                          $force_selector_display Whether storefront selector (checkbox/radio) must be displayed.
                                                      By default, it's hidden on mobile
bool                          $get_company_ids        Whether to get storefront company IDs
bool                          $get_language_ids       Whether to get storefront language IDs
bool                          $get_currency_ids       Whether to get storefront currency IDs
bool                          $get_country_codes      Whether to get storefront county codes
*}
{if $storefronts}
    <div class="table-responsive-wrapper longtap-selection">
        <table class="table table-middle table--relative table-responsive">
            <thead
                    data-ca-bulkedit-default-object="true"
                    data-ca-bulkedit-component="defaultObject"
            >
            <tr>
                <th class="mobile-hide" width="1%">
                    {if $select_mode === "multiple"}
                        {include file="common/check_items.tpl"}

                        <input type="checkbox"
                               class="bulkedit-toggler hide"
                               data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                               data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                        />
                    {/if}
                </th>
                <th>
                    <a class="cm-ajax"
                       href="{"{$sort_url}&sort_by=name&sort_order={$search.sort_order_rev}"|fn_url}"
                       data-ca-target-id="pagination_contents"
                    >
                        {__("name")}
                        {if $search.sort_by == "name"}
                            {$sort_active_icon_class nofilter}
                        {else}
                            {$sort_dummy_icon_class nofilter}
                        {/if}
                    </a>
                </th>

                <th>
                    <a class="cm-ajax"
                       href="{"{$sort_url}&sort_by=url&sort_order={$search.sort_order_rev}"|fn_url}"
                       data-ca-target-id="pagination_contents"
                    >
                        {__("url")}
                        {if $search.sort_by == "url"}
                            {$sort_active_icon_class nofilter}
                        {else}
                            {$sort_dummy_icon_class nofilter}
                        {/if}
                    </a>
                </th>

                {hook name="storefronts:manage_header"}{/hook}

                {if !$is_readonly}
                    <th width="5%" class="nowrap" >
                        &nbsp;
                    </th>
                {/if}

                <th width="15%" class="right">
                    <a class="cm-ajax"
                       href="{"{$sort_url}&sort_by=status&sort_order={$search.sort_order_rev}"|fn_url}"
                       data-ca-target-id="pagination_contents"
                    >
                        {__("storefront_status")}
                        {include file="common/tooltip.tpl"
                            tooltip=__("ttc_stores_status")
                        }
                        {if $search.sort_by == "status"}
                            {$sort_active_icon_class nofilter}
                        {else}
                            {$sort_dummy_icon_class nofilter}
                        {/if}
                    </a>
                </th>
            </tr>
            </thead>

            {foreach $storefronts as $storefront}
                {if $is_readonly}
                    {include file="views/storefronts/components/list_item_readonly.tpl"
                        storefront = $storefront
                        select_mode = $select_mode
                        force_selector_display = $force_selector_display
                        get_company_ids = $get_company_ids
                        get_language_ids = $get_language_ids
                        get_currency_ids = $get_currency_ids
                        get_country_codes = $get_country_codes
                    }
                {else}
                    {include file="views/storefronts/components/list_item.tpl"
                        storefront = $storefront
                        return_url = $return_url
                        select_mode = $select_mode
                        force_selector_display = $force_selector_display
                        get_company_ids = $get_company_ids
                        get_language_ids = $get_language_ids
                        get_currency_ids = $get_currency_ids
                        get_country_codes = $get_country_codes
                    }
                {/if}
            {/foreach}
        </table>
    </div>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}
