<div class="sidebar-field">
    <label for="elm_type">{__("type")}</label>
    <select id="elm_type" name="store_types">
        <option value="" {if empty($search.store_types)} selected="selected"{/if}>{__("warehouses.any_store_type")}</option>
        {foreach $store_types as $type_code => $type_name}
            <option value="{$type_code}"{if $type_code == $search.store_types} selected{/if}>{$type_name}</option>
        {/foreach}
    </select>
</div>