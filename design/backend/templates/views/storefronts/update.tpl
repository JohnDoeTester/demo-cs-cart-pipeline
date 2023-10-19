{$id = $storefront->storefront_id|default:0}

{capture name="mainbox"}
    {capture name="tabsbox"}
        <form id="update_storefront_form_{$id}"
              action="{""|fn_url}"
              method="post"
              name="storefront_update_form"
              class="form-horizontal form-edit cm-disable-empty-files {if $is_form_readonly}cm-hide-inputs{/if}"
        >
            <input type="hidden"
                   name="storefront_data[storefront_id]"
                   value="{$id}"
            />

            <div id="content_general">
                {$name = ""}
                {$url = ""}
                {$status = "StorefrontStatuses::OPEN"|enum}
                {$access_key = ""}
                {$is_default = false}
                {$theme = $config.base_theme}
                {$is_accessible_for_authorized_customers_only = false}

                {if $storefront}
                    {$name = $storefront->name}
                    {$url = $storefront->url}
                    {$status = $storefront->status}
                    {$access_key = $storefront->access_key}
                    {$is_default = $storefront->is_default}
                    {$theme = $storefront->theme_name}
                    {$is_accessible_for_authorized_customers_only = $storefront->is_accessible_for_authorized_customers_only}
                {/if}

                {include file="common/subheader.tpl"
                    title=__("information")
                }

                {include file="views/storefronts/components/name.tpl"
                    id=$id
                    name=$name
                }

                {include file="views/storefronts/components/url.tpl"
                    id=$id
                    url=$url
                }

                {include file="views/storefronts/components/is_default.tpl"
                    id=$id
                    is_default=$is_default
                }

                {include file="views/storefronts/components/status.tpl"
                    id=$id
                    status=$status
                    meta="company-switch-storefront-status-button"
                    extra_attrs=[
                        "data-ca-storefront-id" => $id,
                        "data-ca-opened-status" => {"StorefrontStatuses::OPEN"|enum},
                        "data-ca-closed-status" => {"StorefrontStatuses::CLOSED"|enum},
                        "data-ca-return-url" => {$config.current_url|escape:url},
                        "data-ca-submit-url" => "storefronts.update_status"
                    ]
                }

                {include file="views/storefronts/components/access_key.tpl"
                    id=$id
                    access_key=$access_key
                }

                {include file="views/storefronts/components/access_only_for_authorized_customers.tpl"
                    id=$id
                    is_accessible_for_authorized_customers_only=$is_accessible_for_authorized_customers_only
                }

                {include file="common/subheader.tpl"
                    title=__("design")
                }

                {include file="views/storefronts/components/theme.tpl"
                    id=$id
                    theme=$theme
                    current_style=$current_style
                    current_theme=$current_theme
                }

                {if !$id}
                    <div class="control-group">
                        <label class="control-label">{__("copy_theme_from_another_storefront")}</label>
                        <div class="controls">
                            {include file="views/storefronts/components/picker/picker.tpl"
                                input_name="storefront_data[extra][copy_layouts_from_storefront_id]"
                                show_advanced=false
                            }
                        </div>
                    </div>
                {/if}

                {if $id}
                    {include file="common/subheader.tpl"
                        title=__("localization")
                    }

                    {include file="views/storefronts/components/languages.tpl"
                        id=$id
                        all_language_ids=$all_language_ids
                        all_languages=$all_languages
                        is_localization_picker_allowed=$is_localization_picker_allowed
                    }

                    {include file="views/storefronts/components/currencies.tpl"
                        id=$id
                        all_currency_ids=$all_currency_ids
                        all_currencies=$all_currencies
                        is_localization_picker_allowed=$is_localization_picker_allowed
                    }
                {/if}
            </div>

            <div id="content_regions" class="hidden">
                {$selected_countries = []}
                {$redirect_customer = false}
                {if $storefront}
                    {if isset($selected_countries_list)}
                        {$selected_countries = $selected_countries_list}
                    {/if}
                    {$redirect_customer = $storefront->redirect_customer}
                {/if}

                {include file="views/storefronts/components/redirect_customer.tpl"
                    id=$id
                    redirect_customer = $redirect_customer
                }

                {include file="views/storefronts/components/regions.tpl"
                    id=$id
                    selected_countries=$selected_countries
                    all_countries=$all_countries
                }
            </div>

            <div id="content_companies" class="hidden">
                {$selected_companies = []}
                {if $storefront}
                    {$selected_companies = $storefront->getCompanyIds()}
                {/if}

                {include file="views/storefronts/components/companies.tpl"
                    id=$id
                    selected_companies=$selected_companies
                }
            </div>

            <div id="content_administrators" class="hidden">
                {include file="views/storefronts/components/administrators.tpl"
                    id=$id
                    storefront_admins=$storefront_admins
                }
            </div>
        </form>
    {/capture}

    {include file="common/tabsbox.tpl"
        content=$smarty.capture.tabsbox
        group_name=$runtime.controller
        active_tab=$smarty.request.selected_section
        track=true
    }
{/capture}

{capture name="buttons"}
    {hook name="storefronts:update_buttons"}
        {if ($runtime.mode === "add" && $is_storefronts_limit_reached)}
            {$promo_popup_title = __("mve_ultimate_license_required", ["[product]" => $smarty.const.PRODUCT_NAME])}

            {include file="common/tools.tpl"
                tool_override_meta="btn btn-primary cm-dialog-opener cm-dialog-auto-size"
                tool_href="functionality_restrictions.mve_ultimate_license_required"
                prefix="top"
                hide_tools=true
                title=__("add_storefront")
                link_text=__("create")
                icon=" "
                meta_data="data-ca-dialog-title='$promo_popup_title'"
            }
        {else}
            {include file="buttons/save_cancel.tpl"
                but_role="submit-link"
                but_name="dispatch[storefronts.update]"
                but_target_form="update_storefront_form_{$id}"
                save=$id
            }
        {/if}
    {/hook}
{/capture}

{include file="common/mainbox.tpl"
    title = ($id) ? $storefront->name : __("creating_storefront")
    content=$smarty.capture.mainbox
    buttons=$smarty.capture.buttons
}
