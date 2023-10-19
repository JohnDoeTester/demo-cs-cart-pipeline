<div class="hidden" id="new_thread_dialog_{$object_id}" title="{if $vendor_name}{__("vendor_communication.contact_vendor_name", ["[vendor_name]" => $vendor_name])}{else}{__("vendor_communication.contact_vendor")}{/if}">
    <form action="{""|fn_url}" method="post" class="{if !$no_ajax}cm-ajax{/if} cm-form-dialog-closer" name="add_thread_form_{$object_id}" id="new_thread_form_{$object_id}">
        <input type="hidden" name="result_ids" value="new_thread_message_{$object_id},threads_container" />
        <input type="hidden" name="redirect_url" value="{if $redirect_url}{$redirect_url}{else}{$config.current_url}{/if}" />
        <input type="hidden" name="thread[company_id]" value="{$company_id}" />
        <input type="hidden" name="thread[object_type]" value="{$object_type}" />
        <input type="hidden" name="thread[object_id]" value="{$object_id}" />
        <input type="hidden" name="thread[communication_type]" value="{"Addons\\VendorCommunication\\CommunicationTypes::VENDOR_TO_CUSTOMER"|enum}" />

        {if $product}
            <div class="ty-vendor-communication-product-info-container">
                <div class="ty-vendor-communication-product-info-image">
                    {include file="common/image.tpl" images=$product.main_pair image_width=$settings.Thumbnails.product_cart_thumbnail_width image_height=$settings.Thumbnails.product_cart_thumbnail_height}
                </div>
                <div class="ty-vendor-communication-product-info-header">
                    <h3 class="ty-product-block-title"><bdi>{$product.product}</bdi></h3>
                    {hook name="products:product_additional_info"}
                    {/hook}
                </div>
            </div>
        {/if}

        <div id="new_thread_message_{$object_id}">
            <div class="ty-control-group">
                <label for="thread_msg_{$object_id}" class="ty-control-group__title cm-required cm-trim">
                    {if $vendor_name}
                        {__("vendor_communication.your_message_to_vendor_name", ["[vendor_name]" => $vendor_name])}
                    {else}
                        {__("vendor_communication.your_message_to_admin")}
                    {/if}
                </label>
                <textarea id="thread_msg_{$object_id}" name="thread[message]" class="ty-input-textarea ty-input-text-large" rows="5" cols="72">{$initial_message}</textarea>
            </div>
        <!--new_thread_message_{$object_id}--></div>

        <div class="buttons-container">
            {include file="buttons/button.tpl" but_text=__("send") but_meta="ty-btn__primary cm-post cm-reset-link" but_role="submit" but_name="dispatch[vendor_communication.create_thread]"}
        </div>
    </form>
</div>
