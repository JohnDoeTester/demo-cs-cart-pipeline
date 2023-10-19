{script src="js/tygh/tabs.js"}

{capture name="mainbox"}

{assign var="r_url" value=$config.current_url|escape:url}

<div class="items-container" id="manage_robots">

<form id="robots_form" action="{""|fn_url}" method="post" name="robots_update_form" class="form-horizontal form-edit cm-ajax cm-disable-empty-files">
    <input type="hidden" name="result_ids" value="manage_robots" />

    <div id="manage_robots_content">

    {if fn_allowed_for("ULTIMATE")}
    <div class="control-group disable-overlay-wrap" id="field_robots_content">
        {if !$runtime.company_id && !$runtime.simple_ultimate}
            <div class="disable-overlay" id="robots_logo_disable_overlay"></div>
        {/if}
        <label for="elm_robots_edit" class="control-label">{__("edit_robots")}:</label>

        <div class="controls" id="robots_content">
            <textarea id="elm_robots_robots" name="robots_data[content]" cols="55" rows="12" class="span12">{$robots}</textarea>

            {if !$runtime.company_id}
                <div class="right">
                    {include file="buttons/update_for_all.tpl"
                        display=true
                        object_id="robots"
                        name="robots_data[update_content]"
                        hide_element="robots_uploader"
                        component="robots.robots_uploader"
                    }
                </div>
            {/if}
        </div>
    </div>
    {else}
    <div class="control-group">
        <label for="elm_robots_edit" class="control-label">{__("edit_robots")}:</label>

        <div class="controls">
            <textarea id="elm_robots_robots" name="robots_data[content]" cols="55" rows="12" class="span12">{$robots}</textarea>
        </div>
    </div>
    {/if}

    <!--manage_robots_content--></div>

    {if $smarty.request.is_not_writable}
        {include file="common/subheader.tpl" title=__("ftp_server_options")}
        <div class="control-group">
            <label for="host" class="control-label">{__("host")}:</label>
            <div class="controls">
                <input id="host" type="text" name="ftp_access[ftp_hostname]" size="30" value="{$ftp_access.ftp_hostname}" class="input-text" />
            </div>
        </div>

        <div class="control-group">
            <label for="login" class="control-label">{__("login")}:</label>
            <div class="controls">
                <input id="login" type="text" name="ftp_access[ftp_username]" size="30" value="{$ftp_access.ftp_username}" class="input-text" />
            </div>
        </div>

        <div class="control-group">
            <label for="password" class="control-label">{__("password")}:</label>
            <div class="controls">
                <input id="password" type="password" name="ftp_access[ftp_password]" size="30" value="{$ftp_access.ftp_password}" class="input-text" />
            </div>
        </div>

        <div class="control-group">
            <label for="base_path" class="control-label">{__("ftp_directory")}:</label>
            <div class="controls">
                <input id="base_path" type="text" name="ftp_access[ftp_directory]" size="30" value="{$ftp_access.ftp_directory}" class="input-text" />
            </div>
        </div>

        <div class="buttons-container">
            {include file="buttons/button.tpl" but_role="submit" but_text=__("recheck") but_name="dispatch[robots.check]" but_meta=" "}
            {include file="buttons/button.tpl" but_role="submit" but_text=__("upload_via_ftp") but_name="dispatch[robots.update_via_ftp]"}
        </div>
    {/if}

</form>

<script>
    (function(_, $){
        $(_.doc).on('click', '[data-ca-update-for-all="robots.robots_uploader"]', function(e){
            $('#robots_uploader').toggleClass('disable-overlay-wrap');
            $('#robots_logo_disable_overlay').toggleClass('disable-overlay');
        });
    })(Tygh, Tygh.$);
</script>

<!--manage_robots--></div>

{capture name="buttons"}
    {include file="buttons/save.tpl" but_name="dispatch[robots.update]" but_role="submit-link" but_target_form="robots_update_form"}
{/capture}

{/capture}

{include file="common/mainbox.tpl"
    title=__("robots_title")
    content=$smarty.capture.mainbox
    buttons=$smarty.capture.buttons
    select_storefront=true
    show_all_storefront=!("MULTIVENDOR"|fn_allowed_for)
}
