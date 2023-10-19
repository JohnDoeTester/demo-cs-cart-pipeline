{script src="js/tygh/template_editor.js"}

{assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
{assign var="return_url" value=$config.current_url}
{assign var="return_url_escape" value=$return_url|escape:"url"}

{capture name="mainbox"}

{$id = 0}
{if $document}
    {$id = $document->getId()}
{/if}

{capture name="tabsbox"}

<div id="content_general" class="document-editor__wrapper">
    <form action="{""|fn_url}" method="post" enctype="multipart/form-data" name="document_form" class="form-horizontal">

        <input type="hidden" name="selected_section" id="selected_section" value="{$smarty.request.selected_section}" />
        <input type="hidden" name="result_ids" value="preview_dialog" />

        {if $id}
            <input type="hidden" name="document_id" value="{$id}" />
        {/if}

        <fieldset>
            <div class="control-group ie-redactor">
                <textarea id="elm_document_body_{$id}" name="document[template]" cols="55" rows="14" class="cm-wysiwyg input-textarea-long cm-emltpl-set-active">{$document->getTemplate()}</textarea>
            </div>
        </fieldset>

    </form>

</div>
<div class="hidden" id="content_snippets">
    <div class="btn-toolbar clearfix cm-toggle-button">
        <div class="pull-right">

            {include file="views/snippets/components/adv_buttons.tpl"
                type=$snippet_type
                addon=$document->getAddon()
                result_ids="content_snippets,sidebar_snippets"
                return_url=$return_url
                text=$text
                link_text=__("add_snippet")
            }
        </div>
    </div>
    {include file="views/snippets/components/list.tpl"
        snippets=$snippets
        type=$snippet_type
        addon=$document->getAddon()
        result_ids="content_snippets,sidebar_snippets"
        return_url=$return_url
    }
<!--content_snippets--></div>

{foreach from=$snippets_tables item="snippet_table"}
    <div class="hidden" id="content_snippet_content_{$snippet_table.snippet->getId()}_table_columns">
        {include file="views/snippets/components/table_column_tab.tpl" snippet=$snippet_table.snippet columns=$snippet_table.columns}
    </div>
{/foreach}

{hook name="documents:tabs_extra"}{/hook}

{/capture}
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox track=true}

{if $has_preview}
    {include file="views/documents/preview.tpl" preview=""}
{/if}

{/capture}

{capture name="sidebar"}
    <div class="document-editor__list">
        <div class="sidebar-row">
            <h6>{__("variables")}</h6>
            <ul class="nav nav-list" id="sidebar_variables">
                {foreach from=$variables item="variable"}
                    <li {if $variable->getAttributes()}style="white-space:nowrap;"{/if}>
                        <span class="label hand cm-emltpl-insert-variable" data-ca-target-template="elm_document_body_{$id}" data-ca-template-value="{$variable->getName()}">{$variable->getName()}</span>
                        {$variable_name = $variable->getName()}
                        {if $variable->getAlias()}
                            {$variable_name = $variable->getAlias()}
                            {__("or")}
                            <span class="label hand cm-emltpl-insert-variable" data-ca-target-template="elm_document_body_{$id}" data-ca-template-value="{$variable->getAlias()}">{$variable->getAlias()}</span>
                        {/if}

                        {if $variable->getAttributes()}
                            <span class="icon-plus hand nav-opener"></span>
                            {include file="views/documents/components/variable_attributes.tpl" attributes=$variable->getAttributes() variable={$variable_name} template="elm_document_body_`$id`" }
                        {/if}
                    </li>
                {/foreach}
            </ul>
        </div>


    <div class="sidebar-row" id="sidebar_snippets">
        <h6>{__("snippets")}</h6>
        <ul class="nav nav-list">
            {foreach from=$snippets item="snippet"}
                {if $snippet->getStatus() == "A"}
                    <li><span class="cm-emltpl-insert-variable label label-info hand" data-ca-target-template="elm_document_body_{$id}" data-ca-template-value="{$snippet->getCallTag()}">{$snippet->getCode()}</span></li>
                {/if}
            {/foreach}
        </ul>
    <!--sidebar_snippets--></div>


    {if $email_templates.C || $email.templates.A}
    <div class="sidebar-row document-editor__email-templates" id="sidebar_email_templates">
        <h6>{__("affected_email_templates")}</h6>
        {if $email_templates.C}
            <strong class="document-editor__email-templates__header">{__("customer_notifications")}</strong>
            <ul class="nav nav-list document-editor__email-templates__list">
                {foreach $email_templates.C as $email_template}
                    <li class="document-editor__email-templates__list__item">
                        <a href="{"email_templates.update?template_id={$email_template->getId()}"|fn_url}">{$email_template->getName()}</a>
                    </li>
                {/foreach}
            </ul>
        {/if}
        {if $email_templates.A}
            <strong class="document-editor__email-templates__header">{__("admin_notifications")}</strong>
            <ul class="nav nav-list document-editor__email-templates__list">
                {foreach $email_templates.A as $email_template}
                    <li class="document-editor__email-templates__list__item">
                        <a href="{"email_templates.update?template_id={$email_template->getId()}"|fn_url}">{$email_template->getName()}</a>
                    </li>
                {/foreach}
            </ul>
        {/if}
    <!--sidebar_email_templates--></div>
    {/if}
    </div>

    <script>
        (function(_, $) {
            $(document).ready(function () {
                $('#sidebar_variables').on('click', '.nav-opener', function(e) {
                    var list = $(this).parent().find('ul.nav:first');
                    list.toggleClass('hidden');

                    if ($(this).hasClass('icon-minus')) { //close child lists
                        list.find('ul').addClass('hidden');
                        list.find('.icon-minus').toggleClass('icon-plus icon-minus');
                    }

                    $(this).toggleClass('icon-plus icon-minus');
                });

                $.ceEvent('on', 'ce.update_object_status_callback', function(data, params) {
                    if (typeof data.snippet_id == 'undefined') {
                        return;
                    }

                    var $tab = $('#snippet_content_' + data.snippet_id + '_table_columns');

                    if (data.success && $tab.length) {
                        if (data.new_status != 'A') {
                            $tab.addClass('hidden');
                        } else {
                            $tab.removeClass('hidden');
                        }
                    }
                });
            });
        }(Tygh, Tygh.$));

    </script>
{/capture}

{capture name="import_form"}
    <div class="install-addon">
        <form action="{""|fn_url}" method="post" class="form-horizontal form-edit" name="import_documents" enctype="multipart/form-data">
            <input type="hidden" name="return_url" value="{$config.current_url}"/>
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
    {capture name="tools_list"}
        {hook name="documents:update_tools_list_general"}
            {if $has_preview}
                <li>{btn type="list" text=__("preview") class="cm-ajax cm-form-dialog-opener cm-dialog-auto-size" dispatch="dispatch[documents.preview]" form="document_form"}</li>
            {/if}
            <li>{btn type="text" text=__("export") href="documents.export?document_id=`$document->getId()`"  method="POST"}</li>

            {if fn_check_permissions("documents", "import", "admin", "POST")}
                <li>{include file="common/popupbox.tpl" id="import_form" link_text=__("import") act="link" link_class="cm-dialog-auto-size"  text=__("import") content=$smarty.capture.import_form general_class="action-btn"}</li>
            {/if}

            {if $document->isModified()}
                {assign var="r_url" value=$config.current_url|escape:url}
                <li>{btn type="text" href="documents.restore?document_id=$id&return_url=$r_url" class="cm-confirm" data=["data-ca-confirm-text" => "{__("text_restore_question")}"] text=__("restore") method="POST"}</li>
            {/if}
        {/hook}
    {/capture}
    {dropdown content=$smarty.capture.tools_list class="cm-tab-tools tab-tools" id="tools_general"}

    {hook name="documents:update_buttons_extra"}{/hook}

    {include file="buttons/save_changes.tpl" but_role="action" but_id="document_save" but_name="dispatch[documents.update]" but_target_form="document_form" but_meta="cm-submit btn-primary" save=$document}
{/capture}

{capture name="adv_buttons"}
    {hook name="documents:update_adv_buttons_extra"}{/hook}
{/capture}

{include file="common/mainbox.tpl"
    title=$document->getName()
    content=$smarty.capture.mainbox
    buttons=$smarty.capture.buttons
    adv_buttons=$smarty.capture.adv_buttons
    sidebar=$smarty.capture.sidebar
    sidebar_position="left"
    select_languages=true
}
