{$c_url = $config.current_url|fn_query_remove:"sort_by":"sort_order"}
{if $search.sort_order == "asc"}
    {include_ext file="common/icon.tpl" class="ty-icon-down-dir" assign=sort_sign}
{else}
    {include_ext file="common/icon.tpl" class="ty-icon-up-dir" assign=sort_sign}
{/if}
{if !$config.tweaks.disable_dhtml}
    {$ajax_class = "cm-ajax"}
{/if}

{$rev = $smarty.request.content_id|default:"pagination_contents"}
{$show_subject_image_column = false}

{if "ULTIMATE"|fn_allowed_for}
    {if $addons.vendor_communication.show_on_messages == "Y"}
        {include file="addons/vendor_communication/views/vendor_communication/components/new_thread_button.tpl" object_id=$company_id show_form=true}

        {include
            file="addons/vendor_communication/views/vendor_communication/components/new_thread_form.tpl"
            object_type=$smarty.const.VC_OBJECT_TYPE_COMPANY
            object_id=$company_id
            company_id=$company_id
            vendor_name=$company_id|fn_get_company_name
        }
    {/if}
{/if}

{foreach $threads as $thread}
    {if $thread.object_type === $smarty.const.VC_OBJECT_TYPE_PRODUCT
        || $thread.object_type === $smarty.const.VC_OBJECT_TYPE_COMPANY
    }
        {$show_subject_image_column = true}
    {/if}
{/foreach}

<div id="threads_container">
    {include file="common/pagination.tpl"}

    <table class="ty-table ty-vendor-communication-search" id="threads_table">
        <thead>
        <tr>
            <th width="3%" class="ty-vendor-communication-search__label hidden-phone">&nbsp;</th>
            {if $show_subject_image_column}
                <th width="7%">&nbsp;</th>
            {/if}
            <th width="12%" class="nowrap"><a class="{$ajax_class}" href="{"`$c_url`&sort_by=thread&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("id")}{if $search.sort_by === "thread"}{$sort_sign nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
            <th width="40%">{__("message")}</th>
            <th width="21%">{__("vendor_communication.subject")}</th>
            <th width="17%"><a class="cm-ajax" href="{"`$c_url`&sort_by=last_updated&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("date")}{if $search.sort_by === "last_updated"}{$sort_sign nofilter}{else}{$c_dummy nofilter}{/if}</a></th>

            {hook name="vendor_communication:manage_header"}{/hook}
        </tr>
        </thead>
        {foreach $threads as $thread}
            {$has_new_message = $auth.user_id != $thread.last_message_user_id && $thread.user_status == $smarty.const.VC_THREAD_STATUS_HAS_NEW_MESSAGE}

            <tr>
                <td class="ty-vendor-communication-search__item ty-vendor-communication-search__label hidden-phone">
                    {if $has_new_message}
                        <span class="ty-new__label"></span>
                    {/if}
                </td>
                {if $show_subject_image_column}
                    <td class="ty-vendor-communication-search__item ty-nowrap">
                        {include file="addons/vendor_communication/views/vendor_communication/components/subject_image.tpl"
                            thread=$thread
                        }
                    </td>
                {/if}
                <td class="ty-vendor-communication-search__item ty-vendor-communication-search__thread-id">
                    <a href="{"vendor_communication.view?thread_id=`$thread.thread_id`"|fn_url}">
                        {if $has_new_message}
                            <span class="ty-new__label hidden-desktop hidden-tablet"></span>
                        {/if}
                        <strong>{__("vendor_communication.thread", ["[thread_id]" => $thread.thread_id])}</strong>
                    </a>
                </td>
                <td class="ty-vendor-communication-search__item ty-vendor-communication-search__message">
                    <a class="clearfix {if $thread.new_message}ty-new__text{/if}"
                        href="{"vendor_communication.view?thread_id=`$thread.thread_id`"|fn_url}"
                        data-ca-thread-id="{$thread.thread_id}"
                        title="{$thread.last_message}"
                    >
                        <strong>
                            {if $thread.last_message_user_id == $auth.user_id}
                                {__("vendor_communication.you")}:
                            {elseif $thread.last_message_user_type === "UserTypes::ADMIN"|enum}
                                {__("vendor_communication.admin")}:
                            {elseif $thread.last_message_user_type === "UserTypes::VENDOR"|enum}
                                {$thread.company}:
                            {else}
                                {__("customer")}:
                            {/if}
                        </strong>
                        {$thread.last_message|truncate:300:"...":true}
                    </a>
                </td>
                <td class="ty-vendor-communication-search__item">
                    {include file="addons/vendor_communication/views/vendor_communication/components/subject.tpl"
                        thread=$thread
                    }
                </td>
                <td class="ty-vendor-communication-search__item ty-nowrap">
                    <a class="{if $thread.new_message}ty-new__text{/if}" href="{"vendor_communication.view?thread_id=`$thread.thread_id`"|fn_url}">{$thread.last_updated|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</a>
                </td>

                {hook name="vendor_communication:manage_data"}{/hook}
            </tr>

            <div id="view_thread_{$thread.thread_id}" class="hidden ty-vendor-communication-view-thread" title="{__("vendor_communication.contact_with", ["[thread_id]" => $thread.thread_id, "[thread_company]" => $thread.company])}"></div>
        {foreachelse}
            <tr class="ty-table__no-items">
                <td colspan="7"><p class="ty-no-items">{__("vendor_communication.no_threads_found")}</p></td>
            </tr>
        {/foreach}
        <!--threads_table--></table>


    {include file="common/pagination.tpl"}
<!--threads_container--></div>

{if $active_thread}
    <div class="cm-vendor-communication-thread-dialog-auto-open" data-ca-thread-id="{$active_thread}"></div>
    <div id="view_thread_auto_open_{$active_thread}" class="hidden ty-vendor-communication-view-thread" title="{__("vendor_communication.thread", ["[thread_id]" => $active_thread])}"></div>
{/if}

{capture name="mainbox_title"}{__("vendor_communication.messages")}{/capture}

{script src="js/addons/vendor_communication/vendor_communication.js"}


