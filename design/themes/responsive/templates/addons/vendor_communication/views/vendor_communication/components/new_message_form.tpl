<form action="{""|fn_url}" method="post" class="cm-ajax ty-vendor-communication-new-message" name="add_message_form_{$object_id}" id="add_message_form_{$object_id}">
    <input type="hidden" name="result_ids" value="messages_list_{$object_id},new_message_{$object_id}">
    <input type="hidden" name="message[thread_id]" value="{$object_id}" />
    <input type="hidden" name="communication_type" value="{"Addons\\VendorCommunication\\CommunicationTypes::VENDOR_TO_CUSTOMER"|enum}" />
    {if $redirect_url}
        <input type="hidden" name="redirect_url" value="{$redirect_url}" />
    {/if}
    <div class="ty-vendor-communication-new-message__message" id="new_message_{$object_id}">
        <label for="thread_message_{$object_id}" class="ty-vendor-communication-new-message__title cm-required">{__("your_message")}</label>
        <textarea id="thread_message_{$object_id}"
            name="message[message]"
            class="ty-input-textarea cm-focus cm-required ty-vendor-communication-new-message__input"
            autofocus
            rows="4"
            cols="72"
        ></textarea>
    <!--new_message_{$object_id}--></div>

    <div class="ty-vendor-communication-new-message__buttons">
        {$refresh_href=$refresh_href|default:"vendor_communication.view&thread_id=`$object_id`&result_ids=messages_list_`$object_id`"|fn_url}
        {include
            file="buttons/button.tpl"
            but_id="refresh_thread_`$object_id`"
            but_text=__("refresh")
            but_icon="ty-icon-refresh"
            but_role="action"
            but_href=$refresh_href
            but_target_id="messages_list_`$object_id`"
            but_meta="cm-ajax ty-btn ty-btn__text ty-animation-rotate"
            but_rel="nofollow"
        }
        {include
            file="buttons/button.tpl"
            but_text=__("send")
            but_meta="ty-btn ty-btn__primary cm-post ty-vendor-communication-new-message__send"
            but_role="submit"
            but_name="dispatch[vendor_communication.post_message]"
        }
    </div>
</form>
