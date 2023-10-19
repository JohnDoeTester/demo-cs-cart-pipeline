{capture name="mainbox"}
    {include_ext file="common/icon.tpl" class="icon-`$search.sort_order_rev`" assign=c_icon}
    {include_ext file="common/icon.tpl" class="icon-dummy" assign=c_dummy}
    {$c_url   = $config.current_url|fn_query_remove:"sort_by":"sort_order"}
    {$rev     = $smarty.request.content_id|default:"pagination_contents"}
    {$rev_common = "common_preset_contents"}

    {hook name="import_presets:list"}

        {capture name="mainbox_content_common"}
            {if $common_presets}
                {$show_notification = true}
                {$context_menu_id = "context_menu_{uniqid()}"}

                <h4 class="subheader">
                    {__("advanced_import.common_presets")}
                </h4>

                {capture name="common_presets_table"}
                    <div class="table-responsive-wrapper longtap-selection" id="{$rev_common}">
                        <table width="100%" class="table table-middle table--relative table-responsive">
                            <thead
                                data-ca-bulkedit-default-object="true"
                                data-ca-bulkedit-component="defaultObject"
                            >
                            <tr>
                                <th class="left import-preset__checker mobile-hide">
                                    {if !$company_id}
                                        {include file="common/check_items.tpl" is_check_all_shown=true elms_container="#`$context_menu_id`"}

                                        <input type="checkbox"
                                            class="bulkedit-toggler hide"
                                            data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                                            data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                                        />
                                    {/if}
                                </th>
                                <th class="import-preset__preset"><a class="cm-ajax" href="{"`$c_url`&sort_by=name&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev_common}>{__("name")}{if $search.sort_by === "name"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>

                                <th class="import-preset__last-launch"><a class="cm-ajax" href="{"`$c_url`&sort_by=last_import&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev_common}>{__("advanced_import.last_launch")}{if $search.sort_by === "last_import"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                                <th class="import-preset__last-status"><a class="cm-ajax" href="{"`$c_url`&sort_by=status&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev_common}>{__("advanced_import.last_status")}{if $search.sort_by === "status"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                                <th class="import-preset__file">{__("advanced_import.file")}</th>
                                <th class="import-preset__has-modifiers">{__("advanced_import.has_modifiers")}</th>
                                <th class="import-preset__run">&nbsp;</th>
                                <th class="import-preset__tools">&nbsp;</th>
                            </tr>
                            </thead>
                            <tbody>
                            {foreach $common_presets as $preset}
                                {if !$company_id}
                                    {$allowed_ext = ['csv', 'xml']}
                                    {include file="addons/advanced_import/views/import_presets/components/preset.tpl"
                                        allowed_ext=$allowed_ext
                                    }
                                {else}
                                    {$allowed_ext = [$preset.file_extension]}
                                    {include file="addons/advanced_import/views/import_presets/components/common_preset.tpl"
                                        allowed_ext=$allowed_ext
                                    }
                                {/if}
                            {/foreach}
                            </tbody>
                        </table>
                    <!--{$rev_common}--></div>
                {/capture}

                {include file="common/context_menu_wrapper.tpl"
                    hook="common_import_presets:context_menu"
                    id=$context_menu_id
                    form="manage_common_import_presets_form"
                    object="import_presets"
                    items=$smarty.capture.common_presets_table
                    is_check_all_shown=true
                }

                <h4 class="subheader">
                    {__("advanced_import.your_presets")}
                </h4>
            {/if}
        {/capture}

        {include file="addons/advanced_import/views/import_presets/components/form.tpl"
                wrapper_content=$smarty.capture.mainbox_content_common
                wrapper_extra_id=""
                preffix="common"
        }

        {capture name="mainbox_content_vendors"}
            {if $presets}
                {$context_menu_id = "context_menu_{uniqid()}"}

                {include file="common/pagination.tpl"}

                {capture name="vendor_presets_table"}
                    <div class="table-responsive-wrapper longtap-selection">
                        <table width="100%" class="table table-middle table--relative table-responsive">
                            <thead
                                data-ca-bulkedit-default-object="true"
                                data-ca-bulkedit-component="defaultObject"
                            >
                            <tr>
                                <th class="left import-preset__checker mobile-hide">
                                    {include file="common/check_items.tpl" is_check_all_shown=true}

                                    <input type="checkbox"
                                        class="bulkedit-toggler hide"
                                        data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                                        data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                                    />
                                </th>
                                <th class="import-preset__preset"><a class="cm-ajax" href="{"`$c_url`&sort_by=name&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("name")}{if $search.sort_by === "name"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                                <th class="import-preset__last-launch"><a class="cm-ajax" href="{"`$c_url`&sort_by=last_import&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("advanced_import.last_launch")}{if $search.sort_by === "last_import"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                                <th class="import-preset__last-status"><a class="cm-ajax" href="{"`$c_url`&sort_by=status&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("advanced_import.last_status")}{if $search.sort_by === "status"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                                <th class="import-preset__file">{__("advanced_import.file")}</th>
                                <th class="import-preset__has-modifiers">{__("advanced_import.has_modifiers")}</th>
                                <th class="import-preset__run">&nbsp;</th>
                                <th class="import-preset__tools">&nbsp;</th>
                            </tr>
                            </thead>
                            <tbody>
                            {foreach $presets as $preset}
                                {if $company_id === $preset.company_id}
                                    {$allowed_ext = ["csv", "xml"]}
                                {else}
                                    {$allowed_ext = [$preset.file_extension]}
                                {/if}
                                {include file="addons/advanced_import/views/import_presets/components/preset.tpl"
                                    company_id=$company_id
                                    allowed_ext=$allowed_ext
                                }
                            {/foreach}
                            </tbody>
                        </table>
                    </div>
                {/capture}

                {include file="common/context_menu_wrapper.tpl"
                    id=$context_menu_id
                    form="manage_vendor_import_presets_form"
                    object="import_presets"
                    items=$smarty.capture.vendor_presets_table
                    is_check_all_shown=true
                }

                <div class="clearfix">
                    {include file="common/pagination.tpl" div_id=$smarty.request.content_id}
                </div>
            {else}
                <p class="no-items">{__("no_data")}</p>
            {/if}
        {/capture}

        {include file="addons/advanced_import/views/import_presets/components/form.tpl"
                wrapper_content=$smarty.capture.mainbox_content_vendors
                wrapper_extra_id=""
                preffix="vendor"
        }
    {/hook}
{/capture}

{capture name="buttons"}
    {capture name="tools_items"}
        {hook name="advanced_import:presets_manage_tools_list"}
        {/hook}
    {/capture}
    {dropdown content=$smarty.capture.tools_items}
{/capture}

{capture name="adv_buttons"}
    {include file="common/tools.tpl"
             tool_href="import_presets.add?object_type=`$object_type`"
             prefix="top"
             hide_tools=true
             title=__("advanced_import.add_preset")
             icon="icon-plus"
    }
{/capture}

{include file="common/mainbox.tpl"
         title=__("advanced_import.import_`$object_type`")
         content=$smarty.capture.mainbox
         buttons=$smarty.capture.buttons
         adv_buttons=$smarty.capture.adv_buttons
}

{$smarty.capture.popups nofilter}
