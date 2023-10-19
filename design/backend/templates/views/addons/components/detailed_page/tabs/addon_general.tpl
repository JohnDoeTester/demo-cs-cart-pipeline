<div class="hidden cm-hide-save-button " id="content_detailed">
    <div class="form-horizontal form-edit">

        {* Notifications *}
        {include file="views/addons/components/notification/requires_upgrade.tpl"}

        {* What's new *}
        {if $current_package}
            {include file="common/subheader.tpl" title=__("what_is_new") target="#acc_what_new"}
            <div id="acc_what_new" class="collapse in collapse-visible">
                <div class="control-group">
                    <label class="control-label" for="addon_what_new">
                        {__("version")} {$current_package.file_name}
                        <div class="muted">
                            {$current_package.available_since|date_format:$settings.Appearance.date_format}
                        </div>
                    </label>
                    <div class="controls">
                        {if $current_package.readme}
                            <p>
                                {$current_package.readme nofilter}
                            </p>
                        {else}
                            <p class="muted">
                                {__("addons.no_changelog")}.
                            </p>
                        {/if}
                    </div>
                </div>
            </div>
        {/if}

        {* Where to access this add-on *}
        {if $addon_install_datetime && $addon.menu_items}
            {include file="common/subheader.tpl" title=__("where_access_addon") target="#acc_where_access_addon"}
            <div id="acc_where_access_addon" class="collapse in collapse-visible">
                <div class="control-group">
                    <label class="control-label" for="addon_name">{__("menu_items")}:</label>
                    <div class="controls">
                        {foreach $addon.menu_items as $href => $menu_item}
                            <p>
                                <a href="{fn_url($href)}">
                                    {($menu_item.title) ? $menu_item.title : __($menu_item.id)}
                                    {if $menu_item.parents}
                                        {strip}
                                            (
                                            {foreach $menu_item.parents as $parent name="addon_menu_item_parents"}
                                                {($parent.title) ? $parent.title : __($parent.id)}
                                                {if !$smarty.foreach.addon_menu_item_parents.last} / {/if}
                                            {/foreach}
                                            )
                                        {/strip}
                                    {/if}
                                </a>
                            </p>
                        {/foreach}
                    </div>
                </div>
            </div>
        {/if}

        {* Description *}
        {include file="common/subheader.tpl" title=__("description") target="#acc_description"}
        <div id="acc_description" class="collapse in collapse-visible">

            {* Add-on description *}
            <div>
                {if $addon.marketplace.product.full_description}
                    {capture assign="unsafe_addon_description"}
                        {$addon.marketplace.product.full_description nofilter}
                    {/capture}
                    <p>{$unsafe_addon_description|sanitize_html nofilter}</p>
                {else}
                    <p>{$addon.description nofilter}</p>
                {/if}
            </div>
        </div>
    </div>
<!--content_detailed--></div>
