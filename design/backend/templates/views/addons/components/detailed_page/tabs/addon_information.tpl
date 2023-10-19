<div class="hidden cm-hide-save-button" id="content_information">
    <div class="form-horizontal form-edit">

        {* Add-on name *}
        <div class="control-group">
            <label class="control-label" for="addon_name">{__("name")}:</label>
            <div class="controls">
                <p id="addon_name">{$addon.name|default:"–"}</p>
            </div>
        </div>

        {* Developer *}
        <div class="control-group">
            <label class="control-label" for="addon_developer">{__("developer")}:</label>
            <div class="controls">
                <p class="spaced-child" id="addon_developer">
                    <span>
                        {$addon.supplier|default:"–"}
                    </span>
                    <a href="{"addons.manage&supplier={$addon.supplier}"|fn_url}">{__("show_all_developer_addons")}</a>
                    {if $addon.supplier_page}
                        <a href="{$addon.supplier_page|fn_url}">{__("developer_page")}</a>
                    {/if}
                </p>
            </div>
        </div>

        {* Category *}
        {if $addon.category}
            <div class="control-group">
                <label class="control-label" for="addon_category">{__("category")}:</label>
                <div class="controls">
                    <p class="spaced-child">
                        <span id="addon_category">
                            {$addon.category_name|default:__("addons.other_category")}
                        </span>
                        <a href="{"addons.manage&category_id={$addon.category}"|fn_url}">{__("show_all_category_addons", ["[category]" => $addon.category_name|lower])}</a>
                    </p>
                </div>
            </div>
        {/if}


        {* Compatibility *}
        <div class="control-group">
            <label class="control-label">{__("compatibility")}:</label>
            <div class="controls">
                {if !$addon.snapshot_correct}
                    {if "MULTIVENDOR"|fn_allowed_for && $addon.addon|fn_check_addon_snapshot:"plus"}
                        <p class="text-warning">{__("addons.mve_ult_or_plus_required", ["[product]" => $smarty.const.PRODUCT_NAME])}</p>
                    {else}
                        <p class="text-warning">{__("addons.ult_required", ["[product]" => $smarty.const.PRODUCT_NAME])}</p>
                    {/if}
                {elseif $addon.is_core_addon || $version_compare}
                    <p>{__("addon_is_compatible", ["[product]" => $smarty.const.PRODUCT_NAME])}</p>
                {elseif $compatibility && !$version_compare}
                    <p class="text-warning">{__("addon_required_version", ["[version]" => $compatibility])}</p>
                {else}
                    <p class="muted">{__("unknown")}</p>
                {/if}
            </div>
        </div>

        {* Languages *}
        <div class="control-group">
            <label class="control-label">{__("languages")}:</label>
            <div class="controls">
                {if $addon_languages}
                    {foreach $addon_languages as $language}
                        {if isset($language.variant)}
                            <p>{$language.variant}</p>
                        {else}
                            <p>{$language}</p>
                        {/if}
                    {/foreach}
                {else}
                    <p class="muted">{__("addons.no_information")}</p>
                {/if}
            </div>
        </div>

        {* Add-on ID *}
        <div class="control-group">
            <label class="control-label">{__("addon_id")}:</label>
            <div class="controls">
                <p>{$addon.addon}</p>
            </div>
        </div>
    
    </div>
<!--content_information--></div>
