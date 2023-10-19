{hook name="companies:view"}

{$obj_id=$company_data.company_id}
{$obj_id_prefix="`$obj_prefix``$obj_id`"}
    {include file="common/company_data.tpl" company=$company_data show_name=true show_descr=true show_rating=true show_logo=true show_links=true show_address=true show_location_full=true}
    <div class="ty-company-detail clearfix">

        <div id="block_company_{$company_data.company_id}" class="clearfix">
            <h1 class="ty-mainbox-title">{$company_data.company}</h1>

            <div class="ty-company-detail__top-links clearfix">
                {hook name="companies:top_links"}
                    <div class="ty-company-detail__view-products" id="company_products">
                        <a href="{"companies.products?company_id=`$company_data.company_id`"|fn_url}">{__("view_vendor_products")}
                            ({$company_data.total_products} {__("items")})</a>
                    </div>
                {/hook}
            </div>
            <div class="ty-company-detail__info">
                <div class="ty-company-detail__logo">
                    {$capture_name="logo_`$obj_id`"}
                    {$smarty.capture.$capture_name nofilter}
                </div>
                {capture name = "profile_fields_{$obj_id}"}
                    {foreach $profile_fields["ProfileFieldSections::CONTACT_INFORMATION"|enum] as $field_id => $field_data}
                        {$data_source = $company_data.fields}
                        {if $field_data.is_default === "YesNo::YES"|enum}
                            {$field_id = $field_data.field_name}
                            {$data_source = $company_data}
                        {/if}
                        {if !$data_source[$field_id]}
                            {continue}
                        {/if}
                        {$field_value = $data_source[$field_id]}
                        <div class="ty-company-detail__control-group">
                            {hook name="companies:profile_field_value"}
                                <label class="ty-company-detail__control-label">{$field_data.description}:</label>
                                {if $field_data.field_type === "ProfileFieldTypes::EMAIL"|enum}
                                    <span><a href="mailto:{$field_value}">{$field_value}</a></span>
                                {elseif $field_data.field_type === "ProfileFieldTypes::CHECKBOX"|enum}
                                    <span>{if $field_value === "YesNo::YES"|enum}{__("yes")}{else}{__("no")}{/if}</span>
                                {elseif $field_data.field_type === "ProfileFieldTypes::DATE"|enum}
                                    <span>{$field_value|date_format:"`$settings.Appearance.date_format`"}</span>
                                {elseif $field_data.field_type === "ProfileFieldTypes::RADIO"|enum
                                    || $field_data.field_type === "ProfileFieldTypes::SELECT_BOX"|enum
                                }
                                    <span>{$field_data.values.$field_value}</span>
                                {elseif $field_data.field_type === "ProfileFieldTypes::FILE"|enum && $field_value.file_name}
                                    <span><a href="{$field_value.link|default:""}">{$field_value.file_name}</a></span>
                                {elseif $field_id === "url"} {* FIXME: URL display is hardcoded *}
                                    <span><a href="{$field_value|normalize_url}">{$field_value}</a></span>
                                {elseif $field_data.field_type === "ProfileFieldTypes::PHONE"|enum || ($field_data.autocomplete_type === "phone-full")}
                                    <span><bdi>{$field_value}</bdi></span>
                                {elseif $field_data.field_type === "ProfileFieldTypes::COUNTRY"|enum}
                                    <span><bdi>{$field_value|fn_get_country_name}</bdi></span>
                                {elseif $field_data.field_type === "ProfileFieldTypes::STATE"|enum}
                                    <span><bdi>{$field_value|fn_get_state_name:$company_data.country}</bdi></span>
                                {else}
                                    <span>{$field_value}</span>
                                {/if}
                            {/hook}
                        </div>
                    {/foreach}
                {/capture}

                {if $smarty.capture["profile_fields_{$obj_id}"]|trim}
                    <div class="ty-company-detail__info-list ty-company-detail_info-first">
                        <h5 class="ty-company-detail__info-title">{__("contact_information")}</h5>
                        {$smarty.capture["profile_fields_{$obj_id}"] nofilter}
                    </div>
                {/if}

                {$address="address_`$obj_id`"}
                {$location_full="location_full_`$obj_id`"}
                {if
                    $smarty.capture.$address|trim
                    || $smarty.capture.$location_full|trim
                    || $company_data.country
                }
                    <div class="ty-company-detail__info-list">
                        <h5 class="ty-company-detail__info-title">{__("shipping_address")}</h5>

                        {if $smarty.capture.$address|trim}
                            <div class="ty-company-detail__control-group">
                                <span>{$smarty.capture.$address nofilter}</span>
                            </div>
                        {/if}

                        {if $smarty.capture.$location_full|trim}
                            <div class="ty-company-detail__control-group">
                                <span>{$smarty.capture.$location_full nofilter}</span>
                            </div>
                        {/if}

                        <div class="ty-company-detail__control-group">
                            <span>{$company_data.country|fn_get_country_name}</span>
                        </div>
                    </div>
                {/if}
            </div>
        </div>

        {capture name="tabsbox"}
            <div id="content_description"
                 class="{if $selected_section && $selected_section !== "description"}hidden{/if}">
                {if $company_data.company_description}
                    <div class="ty-wysiwyg-content">
                        {$company_data.company_description nofilter}
                    </div>
                {/if}
            </div>
            {hook name="companies:tabs"}
            {/hook}

        {/capture}
    </div>
    {include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox active_tab=$smarty.request.selected_section}

{/hook}
