{$var_name = "logotypes_image_icon[`$id`]"}
{$id_var_name = "`$prefix`{$var_name|md5}"}
<div class="te-warning-info clearfix">
    <br>
    <input type="hidden" name="is_high_res_{$var_name}" value="{$smarty.const.HIDPI_IS_HIGH_RES_FALSE}" id="is_high_res_{$id_var_name}_hidden" />
    <input type="checkbox" name="is_high_res_{$var_name}" value="{$smarty.const.HIDPI_IS_HIGH_RES_TRUE}" id="is_high_res_{$id_var_name}" {if $addons.hidpi.default_upload_high_res_image === "Y"}checked="checked"{/if}/>
    <label for="is_high_res_{$id_var_name}"> {__("hidpi.upload_high_res_image")}</label>
    <div class="te-description">{__("hidpi.upload_high_res_image.tooltip")}</div>
</div>