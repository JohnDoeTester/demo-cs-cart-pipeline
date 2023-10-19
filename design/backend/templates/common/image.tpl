{strip}

{$image_data = $image|fn_image_to_display:$image_width:$image_height}
{$show_detailed_link = $show_detailed_link|default:true}

{$image_attributes = [
    "src" => $image_data.image_path|default:"",
    "width" => $image_data.width|default:"",
    "height" => $image_data.height|default:"",
    "alt" => $image_data.alt|default:"",
    "title" => $image_data.alt|default:"",
    "class" => $image_css_class|default:""
]}
{if $image_id}
    {$image_attributes["id"] = "image_`$image_id`"}
{/if}
{if $image_data.generate_image}
    {$image_attributes["class"] = "spinner {$image_attributes.class}"}
    {$image_attributes["data-ca-image-path"] = $image_data.image_path}
{/if}

{hook name="common:image"}
    {if $show_detailed_link && ($image || $href)}
        <a class="{$link_css_class}" href="{$href|default:$image.image_path}" {if !$href}target="_blank"{/if}>
    {/if}
    {if $image_data.image_path}
        <img {$image_attributes|render_tag_attrs nofilter} />
    {else}
        <div class="no-image {$no_image_css_class}" style="width: {$image_width|default:$image_height}px; height: {$image_height|default:$image_width}px;">{include_ext file="common/icon.tpl" class="glyph-image" title=__("no_image")}</div>
    {/if}
    {if $show_detailed_link && ($image || $href)}</a>{/if}
{/hook}

{/strip}
