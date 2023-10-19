{$object = $thread.object}

{capture name="mainbox"}
    <div class="vendor_communication__view-thread">
        <div class="messages clearfix" id="messages_list_{$thread_id}">
            {foreach from=$messages item=post}
                {hook name="vendor_communication:items_list_row"}
                <div class="vendor-communication-post__content vendor-communication-post-item
                    {if $post.user_type == "C"}
                        vendor-communication-post__customer
                    {/if}
                    ">
                    <div class="vendor-communication-post__date">
                        {$post.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
                    </div>
                    <div class="vendor-communication-post__img">
                        {if $post.user_type == "V"}
                            {if $auth.user_type === "UserTypes::ADMIN"|enum}
                                {$show_detailed_link = true}
                            {else}
                                {$show_detailed_link = false}
                            {/if}

                            {include
                                file="common/image.tpl"
                                image=$post.vendor_info.logos.theme.image
                                image_width="60"
                                image_height="60"
                                show_detailed_link=$show_detailed_link
                                href={"profiles.update?user_id=`$post.vendor_info.logos.theme.company_id`"|fn_url}
                                class="vendor-communication-logo__image"
                            }
                        {/if}
                        {if $post.user_type === "A"}
                            {include_ext file="common/icon.tpl" class="icon-user"}
                        {/if}
                    </div>
                    <div class="vendor-communication-post__info">
                        <div class="vendor-communication-post {cycle values=", vendor-communication-post_even"}"
                            id="post_{$post.post_id}">
                            <div class="vendor-communication-post__message">{$post.message|nl2br nofilter}</div>
                            <span class="icon-caret">
                                <span class="icon-caret-outer"></span>
                                <span class="icon-caret-inner"></span>
                            </span>
                        </div>
                        <div class="vendor-communication-post__author">
                            {if $post.user_id == $auth.user_id }
                                {__("vendor_communication.you")}
                            {else}
                                {$post.firstname} {$post.lastname}
                            {/if}
                        </div>
                    </div>
                </div>
                {/hook}
            {/foreach}
            <div class="vendor-communication-post__bottom"></div>
        <!--messages_list_{$thread_id}--></div>

        <div class="fixed-bottom">
            <div class="fixed-bottom-wrapper" id="new_message_form_{$thread_id}">
                <form action="{""|fn_url}" method="post" class="cm-ajax add_message_form" name="add_message_form_{$thread_id}"
                    id="add_message_form_{$thread_id}">

                    <input type="hidden" name="result_ids" value="messages_list_{$thread_id},new_message_form_{$thread_id}">
                    <input type="hidden" name="communication_type" value="{$thread.communication_type}"/>
                    <input type="hidden" name="message[thread_id]" value="{$thread_id}" />

                    <div id="new_message_{$thread_id}" class="add_message_form--wrapper">
                        {if $allow_send}
                            <textarea
                                id="thread_message_{$thread_id}"
                                name="message[message]"
                                class="cm-focus add_message_form--textarea"
                                rows="5"
                                autofocus
                                placeholder="{__("vendor_communication.type_message")}"
                                data-ca-vendor-communication="threadMessage"
                            ></textarea>
                        {/if}
                        <div class="buttons-container">
                            {if $thread.thread_id}
                                {include
                                    file="buttons/button.tpl"
                                    but_id="refresh_thread_`$thread_id`"
                                    but_icon="icon-refresh"
                                    but_text=__("refresh")
                                    but_role="action"
                                    but_href="vendor_communication.view?thread_id=`$thread_id`&result_ids=messages_list_`$thread_id`&communication_type=`$thread.communication_type`"|fn_url
                                    but_target_id="messages_list_`$thread_id`"
                                    but_meta="cm-ajax btn btn-link btn-icon-link animation-rotate add_message_form--refresh-btn"
                                    but_rel="nofollow"
                                }
                                {if $allow_send}
                                    {include
                                        file="buttons/button.tpl"
                                        but_text=__("send")
                                        but_meta="btn btn-primary btn-send cm-post pull-right"
                                        but_role="submit"
                                        but_name="dispatch[vendor_communication.post_message]"
                                    }
                                {/if}
                            {else}
                                {include
                                    file="buttons/save_cancel.tpl"
                                    but_text=__("send")
                                    cancel_action="close"
                                    but_meta="btn btn-primary btn-send cm-post pull-right"
                                    but_role="submit"
                                    but_name="dispatch[vendor_communication.post_message]"}
                            {/if}
                        </div>
                    </div>
                </form>
            </div>
        <!--new_message_form_{$thread_id}--></div>
    </div>
{/capture}

{capture name="mainbox_title"}
    {if $thread.thread_id}
        {__("vendor_communication.ticket")} &lrm;#{$thread.thread_id}
        <span class="f-middle">{$thread.firstname} {$thread.lastname} / {$thread.company_id|fn_get_company_name}</span>
        <span class="f-small">
            {assign var="last_updated" value=$thread.last_updated|date_format:"`$settings.Appearance.date_format`"|escape:url} /
            {$thread.last_updated|date_format:"`$settings.Appearance.date_format`"},
            {$thread.last_updated|date_format:"`$settings.Appearance.time_format`"}
        </span>
    {else}
        {__("vendor_communication.contact_admin")}
    {/if}
{/capture}

{capture name="sidebar"}
    {hook name="vendor_communication:view_sidebar"}
        {include file="addons/vendor_communication/views/vendor_communication/components/sidebar_thread_object_data.tpl"
            object=$object
            object_id=$object_id
        }
    {/hook}
{/capture}

{include
    file="common/mainbox.tpl"
    title=$smarty.capture.mainbox_title
    content=$smarty.capture.mainbox
    sidebar=$smarty.capture.sidebar
    adv_buttons=$smarty.capture.adv_buttons
    buttons=$smarty.capture.buttons
    content_id="view_thread"
}
