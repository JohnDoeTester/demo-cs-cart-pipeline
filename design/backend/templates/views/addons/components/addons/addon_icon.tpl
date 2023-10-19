{* Imports *}

{* Constants *}
{$LABEL_LENGTH = 2}
{$ICON_SIZE_MEDIUM = 60}
{$ICON_SIZE_LARGE = 192}

{* Icon size *}
{$size = ($icon_large) ? "large" : "medium"}
{$icon_width = ($icon_large) ? $ICON_SIZE_LARGE : $ICON_SIZE_MEDIUM}

{* Wrapper attributes *}
{$wrapper_class = "addons-addon-icon__wrapper addons-addon-icon__wrapper--`$addon.status|lower` addons-addon-icon__wrapper--`$size`"}
{$title = ($show_description) ? $addon_full_description : ""}

{* Wrapper link *}
{if $href === true && $is_marketplace_addons}
    {$href = $a.addon_marketplace_page}
{elseif $href === true && fn_allowed_for("MULTIVENDOR") && $selected_storefront_id}
    {$href = "addons.update?addon={$addon.addon}"|fn_url|fn_link_attach:"storefront_id={$selected_storefront_id}"}
{elseif $href === true}
    {$href = "addons.update?addon={$addon.addon}"|fn_url}
{/if}

{* Image attributes *}
{$image_dir_path = "`$images_dir`/addons/`$addon.addon`"}
{$icon_class = "addons-addon-icon__image addons-addon-icon__image--`$addon.status|lower` addons-addon-icon__image--`$size`"}

{if $is_marketplace_addons}
    {$is_open_new_tab = true}
    {$icon_width = "241"}
    {$icon_height = "95"}
    {$wrapper_class = "`$wrapper_class` addons-addon-icon__wrapper--marketplace"}
{/if}

{capture name="icon"}
    {if $addon.icon_path}
        <picture>
            <img src="{$images_dir}/{$addon.icon_path}"
                width="{$icon_width}"
                height="{$icon_height}"
                class="{$icon_class}"
            />
        </picture>
    {elseif $is_marketplace_addons}
        <picture>
            <img src="{$addon.addon_marketplace_image}"
                width="{$icon_width}"
                height="{$icon_height}"
                class="{$icon_class}"
            />
        </picture>
    {elseif $addon.has_icon}
        <picture>
            {if $addon.has_svg_icon}
                <source srcset="{$image_dir_path}/icon.svg" type="image/svg+xml">
            {/if}
            {if $addon.has_avif_icon}
                <source srcset="{$image_dir_path}/icon.avif" type="image/avif">
            {/if}
            {if $addon.has_webp_icon}
                <source srcset="{$image_dir_path}/icon.webp" type="image/webp">
            {/if}
            <source srcset="{$image_dir_path}/icon.png" type="image/png"> 
            <img src="{$image_dir_path}/icon.png"
                width="{$icon_width}"
                height="{$icon_width}"
                class="{$icon_class}"
            />
        </picture>
    {else}
        <div class="{$icon_class} addons-addon-icon__image--label">
            {$addon.name|upper|truncate:$LABEL_LENGTH:""}
        </div>
    {/if}
{/capture}

{if $href}
    <a href="{$href}"
        class="{$wrapper_class} addons-addon-icon__wrapper--link"
        {if $title}
            title="{$title}"
        {/if}
        {if $is_open_new_tab}
            target="_blank"
        {/if}
    >
        {$smarty.capture.icon nofilter}
    </a>
{else}
    <div class="{$wrapper_class}"
        {if $title}
            title="{$title}"
        {/if}
    >
        {$smarty.capture.icon nofilter}
    </div>
{/if}
