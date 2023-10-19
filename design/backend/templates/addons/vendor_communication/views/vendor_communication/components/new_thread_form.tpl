{$redirect_url=$return_url|default:"vendor_communication.threads?communication_type={"Addons\\VendorCommunication\\CommunicationTypes::VENDOR_TO_ADMIN"|enum}"|fn_url}
{$communication_type=$communication_type|default:("Addons\VendorCommunication\CommunicationTypes::VENDOR_TO_ADMIN"|enum)}

<div id="new_thread_dialog_{$object_id}">
    <form action="{"vendor_communication.create_thread?communication_type={if $communication_type}{$communication_type}{else}{"Addons\\VendorCommunication\\CommunicationTypes::VENDOR_TO_ADMIN"|enum}{/if}"|fn_url}" method="post" class="form-horizontal form-edit add_message_form" name="add_thread_form_{$object_id}" id="new_thread_form_{$object_id}">
        <input type="hidden" name="result_ids" value="new_thread_message_{$object_id},threads_table" />
        <input type="hidden" name="redirect_url" value="{$redirect_url}" />
        <input type="hidden" name="thread[object_type]" value="{$object_type}" />
        <input type="hidden" name="thread[object_id]" value="{$object_id}" />
        <input type="hidden" name="thread[communication_type]" value="{$communication_type}" />


        <div id="new_message_{$object_id}" class="add_message_form--wrapper">

            <div class="control-group">
                <label for="thread_message_subject_{$object_id}" class="control-label vendor-communication-add-message__title cm-required">{__("subject")}</label>
                <div class="controls">
                    {if $object_type && $object_type != $smarty.const.VC_OBJECT_TYPE_COMPANY}
                        <div class="vendor-communication-object-data pull-right">
                            {include file="addons/vendor_communication/views/vendor_communication/components/thread_object_data.tpl" object=$object new_thread=true}
                        </div>
                    {else}
                        <input type="text" name="thread[subject]" id="thread_message_subject_{$object_id}" class="input-large cm-required"/>
                    {/if}
                </div>
            </div>

            {if $auth.user_type == "UserTypes::ADMIN"|enum}
                {if $communication_type === "Addons\\VendorCommunication\\CommunicationTypes::VENDOR_TO_ADMIN"|enum}
                    <div class="control-group">
                        <label class="control-label vendor-communication-add-message__title cm-required cm-multiple-checkboxes">{__("vendor")}</label>
                        <label class="control-label cm-required hidden" for="thread_message_company_{$object_id}">{__("vendor")}</label>
                            {if $object_type}
                                <div class="controls">
                                    <input type="hidden" name="thread[companies][]" value="{$object.company_id}" />
                                    <div class="additional-info pull-right">{$object.company}</div>
                                </div>
                            {else}
                                <div class="controls">
                                    <label class="checkbox">
                                        <input type="checkbox" name="thread[companies][all]" class="vendor-communication-add-message__all-companies" id="thread_message_all_companies_{$object_id}" value="Y">
                                        {__("all")}
                                    </label>
                                </div>
                                <div class="controls">
                                    <div class="cm-field-container">
                                        {include file="views/companies/components/picker/picker.tpl"
                                            select_id="thread_message_company_{$object_id}"
                                            input_name="thread[companies][]"
                                            multiple=true
                                            show_advanced=false
                                            type="selection"
                                            close_on_select=false
                                            meta="input-large vendor-communication-add-message__company"
                                        }
                                    </div>
                                </div>
                            {/if}
                     </div>
                {else}
                    <input type="hidden" name="thread[company_id]" value="{$object.company_id}" />
                {/if}
            {elseif $auth.user_type == "UserTypes::VENDOR"|enum}
                {if $communication_type === "Addons\\VendorCommunication\\CommunicationTypes::VENDOR_TO_ADMIN"|enum}
                    <input type="hidden" name="thread[companies][]" value="{$auth.company_id}" />
                {else}
                    <input type="hidden" name="thread[company_id]" value="{$object.company_id}" />
                {/if}
            {/if}

            <div class="control-group">
                <label for="thread_message_{$object_id}" class="control-label vendor-communication-add-message__title cm-required">
                    {if $communication_type === "Addons\VendorCommunication\CommunicationTypes::VENDOR_TO_ADMIN"|enum}
                        {if $auth.user_type === "UserTypes::ADMIN"|enum}
                            {__("vendor_communication.your_message_to_vendor_name", ["[vendor_name]" => $object.company])}
                        {else}
                            {__("vendor_communication.your_message_to_admin")}
                        {/if}
                    {else}
                        {__("vendor_communication.your_message_to_customer")}
                    {/if}
                </label>
                <textarea
                        id="thread_message_{$object_id}"
                        name="thread[message]"
                        class="cm-focus add_message_form--textarea cm-required"
                        rows="5"
                        autofocus
                        placeholder="{__("vendor_communication.type_message")}"
                ></textarea>
                <div class="buttons-container">
                    {include
                        file="buttons/save_cancel.tpl"
                        but_text=__("send")
                        cancel_action="close"
                        but_target_form="add_thread_form_{$object_id}"
                    }
                </div>
            </div>
        </div>
    </form>
</div>
{script src="js/addons/vendor_communication/thread_form.js"}
