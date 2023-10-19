{script src="js/tygh/template_editor.js"}

{assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
{assign var="return_url" value=$config.current_url}
{assign var="return_url_escape" value=$return_url|escape:"url"}

{capture name="mainbox"}

{$id = $internal_template->getId()}
{$params = $internal_template->getParams()}

{capture name="tabsbox"}

<div id="content_general">
    <form action="{""|fn_url}" method="post" enctype="multipart/form-data" name="internal_template_form" class="form-horizontal">

    <input type="hidden" name="selected_section" id="selected_section" value="{$smarty.request.selected_section}" />
    <input type="hidden" name="result_ids" value="preview_dialog" />
    <input type="hidden" name="template_id" value="{$id}" />
    <input type="hidden" name="redirect_url" value="{$config.current_url}" />

    <fieldset>
        <div class="control-group">
            <label for="elm_internal_template_subject_{$id}" class="cm-required control-label">{__("subject")}:</label>
            <div class="controls">
                <input id="elm_internal_template_subject_{$id}" type="text" name="internal_template[subject]" value="{$internal_template->getSubject()}" class="span9 cm-emltpl-set-active cm-focus">
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="elm_internal_template_template_{$id}">{__("template")}:</label>
            <div class="controls">
                <textarea id="elm_internal_template_template_{$id}" name="internal_template[template]" cols="55" rows="14" class="span9 cm-emltpl-set-active">{$internal_template->getTemplate()}</textarea>
            </div>
        </div>

        {include file="common/select_status.tpl" input_name="internal_template[status]" id="elm_internal_template_status_{$id}" obj=$internal_template->toArray() hidden=false}

        {if $params_schema}
            {foreach from=$params_schema key=name item=field}
                <div class="control-group">
                    <label class="control-label" for="elm_internal_template_params_{$id}_{$name}">{__($field.title)}:</label>
                    <div class="controls">

                        {if $field.type == 'checkbox'}
                            <input type="hidden" name="internal_template[params][{$name}]" value="N">
                            <input type="checkbox" id="elm_internal_template_params_{$id}_{$name}" name="internal_template[params][{$name}]" value="Y"{if $params.$name == "Y"} checked="checked"{/if} />
                        {elseif $field.type == 'selectbox'}
                            <select name="internal_template[params][{$name}]" id="elm_internal_template_params_{$id}_{$name}">
                                <option value=""> - </option>
                                {foreach from=$field.variants key="variant_key" item="variant_name"}
                                    <option value="{$variant_key}" {if $variant_key == $params.$name} selected="selected"{/if}>{$variant_name}</option>
                                {/foreach}
                            </select>
                        {elseif $field.type == 'checkboxes'}
                            <input type="hidden" name="internal_template[params][{$name}]">
                            {foreach from=$field.variants key="variant_key" item="variant_name"}
                                <label class="checkbox inline" for="elm_internal_template_params_{$id}_{$name}_{$variant_key}">
                                    <input type="checkbox" id="elm_internal_template_params_{$id}_{$name}_{$variant_key}" name="internal_template[params][{$name}][]" value="{$variant_key}"{if $variant_key|in_array:$params.$name} checked="checked"{/if} />
                                    {$variant_name}
                                </label>
                            {/foreach}
                        {elseif $field.type == 'textarea'}
                            <textarea id="elm_internal_template_params_{$id}_{$name}" name="internal_template[params][{$name}]" cols="55" rows="3" class="span9">{$params.$name}</textarea>
                        {else}
                            <input type="text" id="elm_internal_template_params_{$id}_{$name}" name="internal_template[params][{$name}]" value="{$params.$name}" />
                        {/if}
                        {if $field.description}
                            <p class="muted description">{__($field.description)}</p>
                        {/if}
                    </div>
                </div>
            {/foreach}
        {/if}

    </fieldset>

    </form>

</div>

{/capture}
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox track=true}

{include file="views/internal_templates/preview.tpl" preview=[]}

{/capture}

{capture name="sidebar"}
    <div class="sidebar-row">
        <h6>{__("variables")}</h6>
        <ul class="nav nav-list variables-list variables-list--variables">
            {foreach $variables as $variable}
                <li class="variables-list__item">
                    <span class="cm-emltpl-insert-variable label hand"
                          data-ca-template-value="{$variable}"
                    >{$variable}</span>
                </li>
            {/foreach}
        </ul>
    </div>

    <div class="sidebar-row--wrapper" id="sidebar_snippets">
    {if $snippets}
        <div class="sidebar-row" id="sidebar_snippets">
            <h6>{__("snippets")}</h6>
            <ul class="nav nav-list variables-list variables-list--snippets">
                {foreach $snippets as $snippet}
                    {if $snippet->getStatus() == "A"}
                        <li class="variables-list__item">
                            <span class="cm-emltpl-insert-variable label label-info hand"
                                  data-ca-template-value="{$snippet->getCallTag()}"
                            >{$snippet->getCode()}
                            </span>
                            <a class="variables-list__item__edit" href="{"snippets.update&snippet_id=`$snippet->getId()`"|fn_url}" title="{__("edit")}">{include_ext file="common/icon.tpl" class="icon icon-edit"}</a>
                        </li>
                    {/if}
                {/foreach}
            </ul>
        </div>
    {/if}
    <!--sidebar_snippets--></div>


    <div class="sidebar-row--wrapper" id="sidebar_documents">
    {if $documents}
        <div class="sidebar-row" id="sidebar_documents">
            <h6>{__("documents")}</h6>
            <ul class="nav nav-list variables-list variables-list--documents">
                {foreach $documents as $document}
                    <li class="variables-list__item">
                        <span class="cm-emltpl-insert-variable label label-info hand"
                              data-ca-template-value="{$document->getCallTag()}"
                        >{$document->getFullCode()}</span>
                        <a class="variables-list__item__edit" href="{"documents.update&document_id=`$document->getId()`"|fn_url}" title="{__("edit")}">{include_ext file="common/icon.tpl" class="icon icon-edit"}</a>
                    </li>
                {/foreach}
            </ul>
        </div>
    {/if}
    <!--sidebar_documents--></div>
{/capture}

{capture name="buttons"}
    {capture name="tools_list"}
        {hook name="internal_templates:update_tools_list_general"}
            <li>{btn type="list" text=__("send_test_notification") class="cm-ajax" dispatch="dispatch[internal_templates.send]" form="internal_template_form"}</li>
            <li>{btn type="list" text=__("preview") class="cm-ajax cm-form-dialog-opener" dispatch="dispatch[internal_templates.preview]" form="internal_template_form"}</li>

            {if $internal_template->isModified()}
                {assign var="r_url" value=$config.current_url|escape:url}
                <li>{btn type="text" href="internal_templates.restore?template_id=$id&return_url=$r_url" class="cm-confirm" data=["data-ca-confirm-text" => "{__("text_restore_question")}"] text=__("restore") method="POST"}</li>
            {/if}
        {/hook}
    {/capture}
    {dropdown content=$smarty.capture.tools_list class="cm-tab-tools tab-tools" id="tools_general"}

    {include file="buttons/save_cancel.tpl" but_role="submit-link" but_name="dispatch[internal_templates.update]" but_target_form="internal_template_form" save=$id}
{/capture}

{include file="common/mainbox.tpl"
    title=$internal_template->getName()
    content=$smarty.capture.mainbox
    buttons=$smarty.capture.buttons
    sidebar=$smarty.capture.sidebar
    sidebar_position="left"
}

<script>
    $.ceEvent('on', 'ce.formajaxpost_internal_template_form', function () {
        $.ceEvent('trigger', 'ce.notifications_center.reloaded', [function () {
            $('.notifications-center__opener-wrapper a').trigger('click');
        }])
    });
</script>
