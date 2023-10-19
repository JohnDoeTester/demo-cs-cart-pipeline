{capture name="sidebar"}
    {include file="views/notification_settings/components/navigation_section.tpl" active_section=$active_section}
{/capture}

{capture name="mainbox"}

{$r_url=$config.current_url|escape:url}
{$can_update=fn_check_permissions("snippets", "update", "admin", "POST")}
{$edit_link_text=__("edit")}

{if !$can_update}
    {$edit_link_text=__("view")}
{/if}

<form action="{""|fn_url}" method="post" name="manage_documents_form" id="manage_documents_form">
    <input type="hidden" name="return_url" value="{$config.current_url}">

    {capture name="documents_table"}
        <div class="items-container longtap-selection table-responsive-wrapper table-responsive" id="documents_list">
            {if $documents}
                <table width="100%" class="table table-middle table--relative">
                    <thead
                        data-ca-bulkedit-default-object="true" 
                        data-ca-bulkedit-component="defaultObject"
                    >
                        <tr>
                            {if $can_update}
                                <th width="6%" class="left">
                                    {include file="common/check_items.tpl" is_check_all_shown=true}

                                    <input type="checkbox"
                                        class="bulkedit-toggler hide"
                                        data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]" 
                                        data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                                    />
                                </th>
                            {/if}
                            <th width="50%">{__("name")}</th>
                            <th width="35%">{__("code")}</th>
                            {if $can_update}
                                <th width="8%">&nbsp;</th>
                            {/if}
                        </tr>
                    </thead>
                    <tbody>
                    {foreach from=$documents item="document"}
                        <tr class="cm-longtap-target cm-row-item"
                            {if $can_update}
                                data-ca-longtap-action="setCheckBox"
                                data-ca-longtap-target="input.cm-item"
                                data-ca-id="{$document->getId()}"
                            {/if}
                        >
                            {if $can_update}
                                <td width="6%" class="left">
                                    <input type="checkbox" name="document_id[]" value="{$document->getId()}" class="cm-item hide" />
                                </td>
                            {/if}
                            <td width="50%" data-th="{__("name")}">
                                <div class="object-group-link-wrap">
                                    <a href="{"documents.update?document_id=`$document->getId()`"|fn_url}">{$document->getName()}</a>
                                </div>
                            </td>
                            <td width="35%" data-th="{__("code")}">
                                <span class="block">{$document->getFullCode()}</span>
                            </td>
                            <td width="8%" class="nowrap mobile-hide">
                                <div class="hidden-tools">
                                    {capture name="tools_list"}
                                        <li>{btn type="list" text=$edit_link_text href="documents.update?document_id=`$document->getId()`"}</li>
                                        <li>{btn type="text" text=__("export") href="documents.export?document_id=`$document->getId()`" method="POST"}</li>
                                    {/capture}
                                    {dropdown content=$smarty.capture.tools_list}
                                </div>
                            </td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
            {else}
                <p class="no-items">{__("no_data")}</p>
            {/if}
        <!--documents_list--></div>
    {/capture}

    {include file="common/context_menu_wrapper.tpl"
        form="manage_documents_form"
        object="documents"
        items=$smarty.capture.documents_table
        has_permission=$can_update
        is_check_all_shown=true
    }
</form>
{/capture}

{capture name="import_form"}
    <div class="install-addon">
        <form action="{""|fn_url}" method="post" class="form-horizontal form-edit" name="import_documents" enctype="multipart/form-data">
            <div class="install-addon-wrapper">
                <img class="install-addon-banner" src="{$images_dir}/addon_box.png" width="151" height="141" />
                {include file="common/fileuploader.tpl" var_name="filename[]" allowed_ext="xml"}
            </div>
            <div class="buttons-container">
                {include file="buttons/save_cancel.tpl" but_text=__("import") but_name="dispatch[documents.import]" cancel_action="close"}
            </div>
        </form>
    </div>
{/capture}

{capture name="buttons"}
    {capture name="tools_items"}
        {if fn_check_permissions("documents", "import", "admin", "POST")}
            <li>{include file="common/popupbox.tpl" id="import_form" link_text=__("import") act="link" link_class="cm-dialog-auto-size"  text=__("import") content=$smarty.capture.import_form general_class="action-btn"}</li>
        {/if}
    {/capture}

    {dropdown content=$smarty.capture.tools_items}
{/capture}

{include file="common/mainbox.tpl" title=__("documents") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons sidebar=$smarty.capture.sidebar}
