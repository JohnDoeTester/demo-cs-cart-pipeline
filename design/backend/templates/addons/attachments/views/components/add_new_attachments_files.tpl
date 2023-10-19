<div class="btn-toolbar clearfix">
    <div class="pull-right">
        {capture name="add_new_picker"}
            {include file="addons/attachments/views/attachments/update.tpl" attachment=[] object_id=$object_id object_type=$object_type}
        {/capture}
        {include file="common/popupbox.tpl" id="add_new_attachments_files" text=__("new_attachment") link_text=__("add_attachment") content=$smarty.capture.add_new_picker act="general" icon="icon-plus"}
    </div>
</div>
