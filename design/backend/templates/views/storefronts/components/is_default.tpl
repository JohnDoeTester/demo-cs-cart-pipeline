{*
array $id         Storefront ID
bool  $is_default Whether a storefront is the default one
*}
<div class="control-group">
    <label for="is_default_{$id}"
           class="control-label"
    >
        {__("is_default_storefront")}
    </label>
    <div class="controls" id="is_default_{$id}">
        {if $is_forbidden_change_main_info}
            <input type="checkbox"
                {if $is_default}checked{/if}
                disabled
            />
            <input type="hidden"
               name="storefront_data[is_default]"
               value="{if $is_default}{"YesNo::YES"|enum}{else}{"YesNo::NO"|enum}{/if}"
            />
        {else}
            <input type="hidden"
               name="storefront_data[is_default]"
               value="{"YesNo::NO"|enum}"
            />
            <input type="checkbox"
                name="storefront_data[is_default]"
                value="{"YesNo::YES"|enum}"
                {if $is_default}checked{/if}
            />
        {/if}
    </div>
</div>
