{capture name="mainbox"}

{capture name="tabsbox"}
    <div class="upgrade-center" id="content_packages">
        <a id="popup_timeout_check_failed_link" class="cm-dialog-opener cm-dialog-auto-size hidden" data-ca-target-id="popup_timeout_check_failed"></a>

        <div class="hidden upgrade-center_wizard cm-dialog-auto-size {if $timeout_check_failed} cm-dialog-auto-open{/if}" id="popup_timeout_check_failed" title="{__("upgrade_center.warning_msg_timeout_fail")}">
            <div class="upgrade_center_wizard-msg">
                <p class="text-error lead">
                    {__("upgrade_center.warning_msg_timeout_check_failed") nofilter}
                </p>
            </div>
            <div class="buttons-container">
                {include file="buttons/save_cancel.tpl" cancel_action="close" hide_first_button=true}
            </div>
        </div>

        <div class="table-responsive-wrapper upgrade-list" id="upgrade_table">
            <table class="table table-upgrade table-responsive table-responsive-w-titles">
                {foreach $upgrade_packages as $type => $packages}
                    {foreach $packages as $_id => $package}
                        {$package.status = "ObjectStatuses::ACTIVE"|enum}
                        {if $type == "core" || $type == "hotfix"}
                            {$package.icon_path = "cart_logo_short.svg"}
                        {/if}
                        {$id = $_id|replace:".":"_"}
                        <tr id="upgrade_{$id}">
                            <td>
                                <div class="upgrade-center_icon">
                                    {include file="views/addons/components/addons/addon_icon.tpl"
                                        addon=$package
                                    }
                                </div>
                            </td>
                            <td>
                                <div class="upgrade-center_package">
                                    <form id="upgrade_form_{$type}_{$id}" name="upgrade_form_{$type}_{$id}" method="post" action="{fn_url()}" class="form-horizontal form-edit cm-disable-check-changes">
                                        <input type="hidden" name="type" value="{$type}">
                                        <input type="hidden" name="id" value="{$_id}">
                                        <input type="hidden" name="result_ids" value="install_notices_{$id},install_button_{$id}">
                                        <input type="hidden" name="return_url" value="{$config.current_url|fn_url}">

                                        <div class="hidden upgrade-center_wizard" id="content_upgrade_center_wizard_{$id}" title="{__("warning")}">
                                            <div class="upgrade_center_wizard-msg">
                                                <p class="text-error lead">
                                                    {__("upgrade_center.warning_msg_upgrade_is_complicated") nofilter}
                                                </p>
                                                <blockquote>
                                                    <p>{__("upgrade_center.warning_msg_specialists", ['[upgrade_center_specialist]'=>$config.resources.upgrade_center_specialist_url, '[upgrade_center_team]'=>$config.resources.upgrade_center_team_url]) nofilter}</p>
                                                    <br>
                                                    <p>{__("upgrade_center.warning_msg_third_party_add_ons")}</p>
                                                    <br>
                                                    <p>{__("upgrade_center.warning_msg_test_local")}</p>
                                                    <br>
                                                    <p>{__("upgrade_center.warning_msg_after_upgrade")}</p>
                                                    <br>
                                                    <p>{__("upgrade_center.warning_msg_generally")}<br><br>
                                                        <input type="submit" name="dispatch[upgrade_center.check_timeout]" class="upgrade-center_check_timeout btn cm-ajax cm-comet cm-post" value="{__("check_php_timeout")}">
                                                    </p>
                                                    <br>
                                                </blockquote>
                                            </div>
                                            <div class="buttons-container">
                                                {if $package.backup.is_skippable}
                                                <label class="pull-left skip-backup">
                                                    <input id="skip_backup" type="checkbox" name="skip_backup" value="Y"{if $package.backup.skip_by_default} checked="checked"{/if} />
                                                    <span>{__("upgrade_center.skip_backup")}</span>
                                                </label>
                                                {/if}
                                                <div class="btn-group btn-hover dropleft">
                                                    <input type="submit" name="dispatch[upgrade_center.install]" class="btn btn-primary cm-ajax cm-comet cm-dialog-closer" value="{__("i_agree_continue")}">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="upgrade-center_item">
                                            <div class="upgrade-center_content">
                                                <h4 class="upgrade-center_title">{$package.name}</h4>
                                                <ul class="upgrade-center_info">
                                                    <li><small class="muted">{$package.timestamp|date_format}</small></li>
                                                    <li><small class="muted">{$package.size|formatfilesize nofilter}</small></li>
                                                </ul>
                                                <div class="upgrade-center_desc">
                                                    {$package.description nofilter}
                                                </div>

                                                {if $type === "core" && $_id === "core" && !empty($package.incompatible_addons)}
                                                    {capture name="incompatible_addons_query"}addons.manage?{foreach $package.incompatible_addons as $incompatible_addon_name}names[]={$incompatible_addon_name}&{/foreach}{/capture}
                                                    <div class="alert alert-muted">
                                                        {__("upgrade_center.incompatible_third_party_addons")}
                                                        <a href="{$smarty.capture.incompatible_addons_query|fn_url:"A"}" target="_blank">{__("upgrade_center.check_addons")}</a>
                                                    </div>
                                                {/if}
                                                {include file="views/upgrade_center/components/notices.tpl" id=$id type=$type}
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </td>
                            <td class="nowrap right">
                                <div class="upgrade-center__actions">
                                    {if $package.ready_to_install}
                                        {include file="views/upgrade_center/components/install_button.tpl"
                                            id=$id
                                            _id=$_id
                                            caption=__("install")
                                            form="upgrade_form_`$type`_`$id`"
                                            show_package_contents=true
                                            package_name=$package.name
                                        }
                                    {else}
                                        <div class="upgrade-center_install">
                                            <input form="upgrade_form_{$type}_{$id}"
                                                name="dispatch[upgrade_center.download]"
                                                type="submit"
                                                class="btn cm-loading-btn {if $type == "core" || $type == "hotfix"}btn-primary{/if}"
                                                value="{__("download")}"
                                                data-ca-loading-text="{__("loading")}">
                                        </div>
                                    {/if}
                                </div>
                            </td>
                        <!--upgrade_{$id}--></tr>
                    {/foreach}
                {foreachelse}
                    <p class="no-items">{__('text_no_upgrades_available')}</p>
                {/foreach}
            </table>
        <!--upgrade_table--></div>
    <!--content_packages--></div>

    <div class="upgrade-center hidden" id="content_installed_upgrades">
        <div class="table-responsive-wrapper upgrade-list" id="installed_upgrades">
            <table class="table table-upgrade table-responsive table-responsive-w-titles">
                {foreach $installed_packages as $_id => $package}
                    {$package.status = "ObjectStatuses::ACTIVE"|enum}
                    {if $package.type == "core" || $package.type == "hotfix"}
                        {$package.icon_path = "cart_logo_short.svg"}
                    {/if}
                    {$id = $_id|replace:".":"_"}
                    <tr id="installed_upgrade_{$_id}">
                        <td>
                            <div class="upgrade-center_icon">
                                {include file="views/addons/components/addons/addon_icon.tpl"
                                    addon=$package
                                }
                            </div>
                        </td>
                        <td>
                            <div class="upgrade-center_item">
                                <div class="upgrade-center_content">
                                    <h4 class="upgrade-center_title">{$package.name}</h4>
                                    <ul class="upgrade-center_info">
                                        <li> <strong>{__("upgraded_on")}:</strong> {$package.timestamp|date_format}</li>
                                    </ul>
                                    <p class="upgrade-center_desc">
                                        {$package.description nofilter}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="nowrap right">
                            {if !empty($package.conflicts)}
                                <div class="upgrade-center__actions">
                                    <div class="upgrade-center_install">
                                        <a class="upgrade-center_pkg cm-dialog-opener cm-ajax btn" href="{"upgrade_center.conflicts?package_id=`$package.id`"|fn_url}" data-ca-target-id="conflicts_content_{$package.id}" data-ca-dialog-title="{$package.name|escape}">{__("local_modifications")}</a>
                                    </div>
                                </div>
                            {/if}
                        </td>
                    <!--installed_upgrade_{$_id}--></tr>
                {foreachelse}
                    <p class="no-items">{__('no_data')}</p>
                {/foreach}
            </table>
        <!--installed_upgrades--></div>
    <!--content_installed_upgrades--></div>
    {literal}
    <script>

        (function(_, $){
            $('.cm-loading-btn').on('click', function() {
                var self = $(this);
                setTimeout(function() {
                    self.prop('value', self.data('caLoadingText'));
                    $('.cm-loading-btn').attr('disabled', true);
                }, 50);
                return true;
            });

            $('.upgrade-center_check_timeout').on('click', function() {
                var timer;
                var millisecBeforeShowMsg = 365000;

                $.ceEvent('on', 'ce.progress_init', function(o) {
                    timer = window.setTimeout(function() {
                        $.toggleStatusBox('hide');
                        $.ceDialog('get_last').ceDialog('close');
                        $('#popup_timeout_check_failed_link').trigger('click');
                        $('#comet_control, .modal-backdrop').remove();
                    }, millisecBeforeShowMsg);
                });

                $.ceEvent('on', 'ce.progress_finish', function(o) {
                    if(timer) {
                        window.clearTimeout(timer);
                        timer = null;
                    }
                });
            });

        })(Tygh, Tygh.$);
    </script>
    {/literal}

{/capture}
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox active_tab=$selected_section track=true}

{/capture}

{capture name="buttons"}
    {capture name="tools_list"}
        <li>{btn type="list" text=__("refresh_packages_list") href="upgrade_center.refresh"}</li>
        <li>{btn type="list" text=__("settings") href="settings.manage&section_id=Upgrade_center"}</li>
    {/capture}
    {dropdown content=$smarty.capture.tools_list}
    {$smarty.capture.install_btn nofilter}
    {if $installed_upgrades.has_upgrades}
        {include file="buttons/button.tpl" but_href="upgrade_center.installed_upgrades" but_text=__("installed_upgrades") but_role="link"}
    {/if}
{/capture}

{capture name="upload_upgrade_package"}
    {include file="views/upgrade_center/components/upload_upgrade_package.tpl"}
{/capture}

{capture name="adv_buttons"}
    {hook name="upgrade_center:adv_buttons"}
        {include file="common/popupbox.tpl" id="upload_upgrade_package_container" text=__("upload_upgrade_package") title=__("upload_upgrade_package") content=$smarty.capture.upload_upgrade_package act="general" link_class="cm-dialog-auto-size" icon="icon-plus" link_text=""}
    {/hook}
{/capture}

{capture name="sidebar"}
    <div class="sidebar-row">
        {if $latest_version_upgrade}
            <h6>{__("product_env.dont_miss_features")}</h6>
        {elseif $available_core_upgrade}
            <h6>{__("product_env.upgrade_to_latest_version")}</h6>
        {else}
            <h6>{__("product_env.up_to_date")}</h6>
        {/if}
        {include file = "common/product_release_info.tpl" assign = "release_info"}
        <p>{__("product_env.now_running", ["[release_info]" => $release_info|trim])}.</p>
        {if $available_core_upgrade}
            {include file = "common/product_release_info.tpl" env_provider = $available_core_upgrade assign = "release_info"}
            <p>{__("product_env.can_upgrade_store", ["[release_info]" => $release_info|trim])}.</p>
        {/if}
        {if $latest_version_upgrade}
            {include file = "common/product_release_info.tpl" env_provider = $latest_version_upgrade assign = "release_info"}
            <p>{__("product_env.latest_product_version", ["[release_info]" => $release_info|trim])}.</p>
        {/if}
    </div>
    {if $upgrade_packages.addon || $search}
        <div class="sidebar-row">
            <form action="{""|fn_url}" name="upgrade_center_filters" method="get" class="{$form_meta}" id="upgrade_center_filters">
                {if $developers || $search}
                    <div class="sidebar-field">
                        <strong>{__("developers")}</strong>
                        {foreach $developers as $developer_key => $developer}
                            <label class="control-label checkbox" for="supplier_{$developer_key}">
                                <input type="checkbox" id="supplier_{$developer_key}" name="supplier[]" value="{$developer.title}" {if $developer.title|in_array:$search.supplier} checked="checked"{/if}>
                                <span>
                                {$developer.title} ({$developer.position})
                            </span>
                            </label>
                        {/foreach}
                    </div>
                {/if}

                <div class="sidebar-field">
                    <label for="addon_status"><strong>{__("status")}</strong></label>
                    <select id="addon_status" name="status">
                        <option value="any" {if empty($search.status) || $search.status == "any"} selected="selected"{/if}>{__("any")}</option>
                        <option value="A" {if $search.status === "A"} selected="selected"{/if}>{__("active")}</option>
                        <option value="D" {if $search.status === "D"} selected="selected"{/if}>{__("disabled")}</option>
                    </select>
                </div>

                <div class="sidebar-field advanced-search-field">
                    <input class="btn" type="submit" name="dispatch[upgrade_center.manage]" value="{__("admin_search_button")}">
                    <a class="btn btn-text" href="{"upgrade_center.manage.reset_view"|fn_url}">{__("reset")}</a>
                </div>
            </form>
        </div>
    {/if}
{/capture}

{include file="common/mainbox.tpl"
    title=__("upgrade_center")
    content=$smarty.capture.mainbox
    buttons=$smarty.capture.buttons
    adv_buttons=$smarty.capture.adv_buttons
    sidebar=$smarty.capture.sidebar
}
