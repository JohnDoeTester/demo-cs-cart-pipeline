{if $runtime.company_id}
    {if $vendor_payments || $is_allow_update_payments}
        <h4 class="subheader">
            {__("direct_payments.your_payments")}
            {if !$show_admin_payments_notification}
                <span class="label label-success">{__("active")}</span>
            {/if}
        </h4>
    {/if}

    {if $show_admin_payments_notification && $is_allow_update_payments}
        <div class="alert alert-block">
            <p>{__("direct_payments.admin_payments_notification")}</p>
            <p>
                {include file="buttons/button.tpl"
                    but_external_click_id="opener_add_new_payments"
                    but_meta="cm-external-click"
                    but_text="{__("direct_payments.create_payment_method")}"
                }
            </p>
        </div>
    {/if}

    <div 
        {if $is_allow_update_payments}
            class="cm-sortable"
            data-ca-sortable-table="payments" data-ca-sortable-id-name="payment_id"
        {/if}
         id="vendor_payments_list"
    >
        {if $vendor_payments}
            <div class="table-responsive-wrapper">
                <table class="table table-middle table--relative table-objects table-striped table-responsive table-responsive-w-titles">
                    <tbody>
                    {foreach $vendor_payments as $vendor_payment}
                        {if $vendor_payment.processor_status == "D"}
                            {$status = "D"}
                            {$can_change_status = false}
                            {$display= "text"}
                        {else}
                            {$status = $vendor_payment.status}
                            {$can_change_status = true}
                            {$display= ""}
                        {/if}

                        {capture name="tool_items"}
                            {hook name="payments:list_extra_links"}{/hook}
                        {/capture}

                        {capture name="extra_data"}
                            {hook name="payments:extra_data"}{/hook}
                        {/capture}

                        {if $is_allow_update_payments}
                            {$additional_class="cm-sortable-row cm-sortable-id-`$vendor_payment.payment_id`"}
                        {else}
                            {$additional_class=""}
                        {/if}

                        {include file="common/object_group.tpl"
                                id=$vendor_payment.payment_id
                                text=$vendor_payment.payment
                                status=$status
                                href="payments.update?payment_id=`$vendor_payment.payment_id`"
                                object_id_name="payment_id"
                                table="payments"
                                href_delete="payments.delete?payment_id=`$vendor_payment.payment_id`"
                                delete_target_id="vendor_payments_list,pagination_contents"
                                skip_delete=$skip_delete
                                header_text=$vendor_payment.payment
                                additional_class=$additional_class
                                no_table=true
                                draggable=$is_allow_update_payments
                                can_change_status=$can_change_status
                                display=$display
                                tool_items=$smarty.capture.tool_items
                                extra_data=$smarty.capture.extra_data
                        }
                    {/foreach}
                    </tbody>
                </table>
            </div>
        {/if}
    <!--vendor_payments_list--></div>

    <h4 class="subheader">
        {__("direct_payments.admin_payments")}
        {if $show_admin_payments_notification}
            <span class="label label-success">{__("active")}</span>
        {/if}
    </h4>

    <div class="alert-block">
        {__("direct_payments.admin_payments_description")}
    </div>

    {$non_editable = true scope="parent"}
    {$draggable = false scope="parent"}
    {$nostatus = true scope="parent"}
{/if}