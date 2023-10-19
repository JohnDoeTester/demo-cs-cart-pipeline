{if "ULTIMATE"|fn_allowed_for}
    {if ($runtime.company_id && $product_data.shared_product == "YesNo::YES"|enum && $product_data.company_id != $runtime.company_id)}
        {$hide_for_vendor=true}
        {$skip_delete=true}
        {$hide_inputs="cm-hide-inputs"}
        {$edit_link_text=__("view")}
    {/if}
{/if}

{$redirect_url=$config.current_url|escape:url}
{$hide_add_new_attachments=$hide_add_new_attachments|default:false}

<div class="items-container cm-sortable" data-ca-sortable-table="attachments" data-ca-sortable-id-name="attachment_id" id="attachments_list">

{if !$hide_for_vendor && !$hide_add_new_attachments}
    {include file="addons/attachments/views/components/add_new_attachments_files.tpl"
        attachment=[]
        object_id=$object_id
        object_type=$object_type
    }
{/if}

{if $attachments}
<div class="table-responsive-wrapper">
    <table class="table table-middle table--relative table-objects table-responsive table-responsive-w-titles">
    {foreach from=$attachments item="a"}
        {capture name="object_group"}
            {include file="addons/attachments/views/attachments/update.tpl" attachment=$a object_id=$object_id object_type=$object_type hide_inputs=$hide_inputs}
        {/capture}
        {include file="common/object_group.tpl"
            content=$smarty.capture.object_group
            id=$a.attachment_id
            text=$a.description
            status=$a.status
            object_id_name="attachment_id"
            table="attachments"
            href_delete="attachments.delete?attachment_id=`$a.attachment_id`&object_id=`$object_id`&object_type=`$object_type`&redirect_url=`$redirect_url`"
            delete_target_id="attachments_list"
            header_text="{__("editing_attachment")}: `$a.description`" additional_class="cm-sortable-row cm-sortable-id-`$a.attachment_id`"
            id_prefix="_attachments_"
            prefix="attachments"
            hide_for_vendor=$hide_for_vendor
            skip_delete=$skip_delete
            no_table="true"
            link_text=$edit_link_text
            draggable=true
        }
    {/foreach}
    </table>
</div>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

<!--attachments_list--></div>