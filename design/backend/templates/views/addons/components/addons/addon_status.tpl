{$show_addon_status = $show_addon_status|default:true}

{if $show_addon_status}
    {$target_id="addons_list,header_navbar,header_subnav,addons_counter,elm_developer_pages,elm_all_dev_pages"}
    {$c_url = $config.current_url}

    <div>
        {if $a.status !== "ObjectStatuses::NEW_OBJECT"|enum}
            <div class="hidden-tools">

            {capture name="tools_list"}
                {if !$is_storefront_admin}
                    {if !$a.is_core_addon && $a.identified && !$a.personal_review && !$a.hide_post_review}
                        <li class="dropdown__item wrap-normal">
                            {include file="views/addons/components/rating/enjoying_addon_notification.tpl"
                                addon=$a
                                id="addons_write_review_manage_`$a.addon`"
                                is_big_heading=false
                            }
                        </li>
                        <li class="divider"></li>
                    {/if}
                    {if $a.upgrade_available}
                        <li>{btn type="list" text=__("upgrade") href="upgrade_center.manage" class="text-success"}</li>
                        <li class="divider"></li>
                    {/if}
                    {if $a.refresh_url}
                        <li>
                            {btn type="list"
                                text=__("refresh")
                                href=$a.refresh_url
                                method="POST"
                            }
                        </li>
                    {/if}
                    {if $a.status === "ObjectStatuses::ACTIVE"|enum}
                        <li>
                            <a class="cm-ajax cm-post cm-ajax-full-render"
                                data-ca-target-id="{$target_id}"
                                href="{"addons.update_status?id=`$key`&status={"ObjectStatuses::DISABLED"|enum}&redirect_url=`$c_url|escape:url`"|fn_url}"
                                data-ca-event="ce.update_object_status_callback"
                            >
                                {__("disable")}
                            </a>
                        </li>
                    {/if}
                    {if $a.delete_url}
                        {$btn_delete_data = [
                            "data-ca-target-id"=>"addons_list,header_navbar,header_subnav"
                        ]}
                        {if isset($a.confirmation_deleting)}
                            {$btn_delete_data["data-ca-confirm-text"] = $a.confirmation_deleting}
                        {/if}

                        <li>
                            {btn type="list"
                                class="cm-confirm text-error"
                                text=__("uninstall")
                                data=$btn_delete_data
                                href=$a.delete_url
                                method="POST"
                            }
                        </li>
                    {/if}
                {/if}
            {/capture}

            {dropdown content=$smarty.capture.tools_list icon=(($a.upgrade_available) ? "icon-cloud-download" : "icon-cog")}

            </div>
        {/if}
    </div>
{/if}
