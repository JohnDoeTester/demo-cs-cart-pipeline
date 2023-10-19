{** block-description:tmpl_logo **}
<div class="ty-logo-container">
    {$logo_link = $block.properties.enable_link|default:"Y" == "Y"}

    {if $logo_link}
        <a href="{""|fn_url}" title="{$logos.theme.image.alt}">
    {/if}
    {include file="common/image.tpl"
             images=$logos.theme.image
             class="ty-logo-container__image"
             image_additional_attrs=["width" => $logos.theme.image.image_x, "height" => $logos.theme.image.image_y]
             obj_id=false
             show_no_image=false
             show_detailed_link=false
             capture_image=false
    }
    
    {if $logo_link}
        </a>
    {/if}
</div>
