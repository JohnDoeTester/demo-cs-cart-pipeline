{$id = $logo.logo_id|default:0}
{$image = $logo.image|default:[]}

{if $id}
    <input type="hidden" name="logotypes_image_data[{$type}][type]" value="M">
    <input type="hidden" name="logotypes_image_data[{$type}][object_id]" value="{$id}">
    <div class="logos-section__item attach-images control-group">
        <div class="upload-box clearfix">
            <div class="row-fluid">
                <div class="span12">
                    <h5>{__("logo_section.{$type}")}</h5>
                </div>
            </div>
            <div class="row-fluid">
                <div class="logos-section__image {$type} span4">
                    <div class="image {$type}">
                        {if $image}
                            <img class="solid-border" src="{$image.image_path}" width="152">
                        {else}
                            <div class="no-image">{include_ext file="common/icon.tpl" class="glyph-image" title=__("no_image")}</div>
                        {/if}
                    </div>
                    {if $show_alt|default:true}
                        <div class="image-alt">
                            <div class="input-prepend">
                                <span class="add-on cm-tooltip" title="{__("alt_text")}">{include_ext file="common/icon.tpl" class="icon-comment"}</span>
                                <input type="text" class="input-text cm-image-field" id="alt_text_{$type}" name="logotypes_image_data[{$type}][image_alt]" value="{$image.alt|default:$company_name}" value="">
                            </div>
                        </div>
                    {/if}
                </div>

                <div class="logos-section image-upload span8">
                    {include file="common/fileuploader.tpl"
                        var_name="logotypes_image_icon[`$type`]"
                        is_image=true
                        show_hidpi_checkbox=$show_hidpi_checkbox|default:true
                    }
                    {if $description}
                        <div>{$description}</div>
                    {/if}
                </div>
            </div>
        </div>
    </div>
{/if}
