{assign var="return_url" value=$config.current_url}

{capture name="mainbox"}
{capture name="tabsbox"}

{$can_update = fn_check_permissions('snippets', 'update', 'admin', 'POST')}
{$edit_link_text = __("edit")}

{if !$can_update}
    {$edit_link_text = __("view")}
{/if}

{if !$groups}
    <p class="no-items">{__("no_data")}</p>
{/if}

{foreach $groups as $group_id => $group}

<div id="content_internal_templates_{$group_id}" class="hidden">
<div class="items-container">
    <div class="table-responsive-wrapper">
        <table class="table table-middle table--relative table-objects table-responsive table-responsive-w-titles">
            <tbody>
                {foreach from=$group item="internal_template"}
                    {include file="common/object_group.tpl"
                        id_prefix=$group_id
                        id=$internal_template->getId()
                        text=$internal_template->getName()
                        status=$internal_template->getStatus()
                        href="internal_templates.update?template_id=`$internal_template->getId()`"
                        object_id_name="template_id"
                        table="template_internal_notifications"
                        href_delete=""
                        delete_target_id=""
                        skip_delete=true
                        header_text=$internal_template->getName()
                        no_popup=true
                        no_table=true
                        draggable=false
                        link_text=$edit_link_text
                        nostatus=!$can_update
                    }
                {/foreach}
            </tbody>
        </table>
    </div>
</div>
<!--content_internal_templates_{$group_id}--></div>
{/foreach}

{/capture}
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox}


{capture name="import_form"}
    <div class="install-addon">
        <form action="{""|fn_url}" method="post" class="form-horizontal form-edit" name="import_internal_templates" enctype="multipart/form-data">
            <div class="install-addon-wrapper">
                <img class="install-addon-banner" src="{$images_dir}/addon_box.png" width="151" height="141" />
                {include file="common/fileuploader.tpl" var_name="filename[]" allowed_ext="xml"}
            </div>
            <div class="buttons-container">
                {include file="buttons/save_cancel.tpl" but_text=__("import") but_name="dispatch[internal_templates.import]" cancel_action="close"}
            </div>
        </form>
    </div>
{/capture}
{include file="common/popupbox.tpl" text=__("import") content=$smarty.capture.import_form id="import_internal_templates_form"}

{capture name="buttons"}
    {capture name="tools_items"}
        {if $groups}
            <li>{btn type="text" href="internal_templates.export" text=__("export") method="POST"}</li>
        {/if}
        {if fn_check_permissions("internal_templates", "import", "admin", "POST")}
            <li>{include file="common/popupbox.tpl" id="import_internal_templates_form" link_text=__("import") act="link" link_class="cm-dialog-auto-size" content="" general_class="action-btn"}</li>
        {/if}
    {/capture}

    {dropdown content=$smarty.capture.tools_items class="cm-tab-tools tab-tools hidden" id="tools_internal_templates_C"}
    {dropdown content=$smarty.capture.tools_items class="cm-tab-tools tab-tools hidden" id="tools_internal_templates_A"}
{/capture}

{/capture}
{include file="common/mainbox.tpl"
    title=__("internal_templates")
    content=$smarty.capture.mainbox
    buttons=$smarty.capture.buttons
    adv_buttons=$smarty.capture.adv_buttons
}
