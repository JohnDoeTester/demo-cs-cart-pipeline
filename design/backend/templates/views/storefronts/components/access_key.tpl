{*
array  $id         Storefront ID
string $access_key Storefront access key
string $input_name Input name
*}

{$input_name = $input_name|default:"storefront_data[access_key]"}

<div class="control-group">
    <label for="access_key_{$id}"
           class="control-label"
    >
        {__("storefront_access_key")}
    </label>
    <div class="controls">
        <input type="text"
               id="access_key_{$id}"
               name="{$input_name}"
               class="input-large"
               value="{$access_key}"
        />
    </div>
</div>
