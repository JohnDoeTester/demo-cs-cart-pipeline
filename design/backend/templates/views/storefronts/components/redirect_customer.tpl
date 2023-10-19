{*
array  $id                Storefront ID
bool   $redirect_customer Whether to redirect customers from this storefront
string $input_name        Input name
*}

{$input_name = $input_name|default:"storefront_data[redirect_customer]"}

<div class="control-group">
    <div class="controls">
        <input type="hidden"
               name="{$input_name}"
               value="{"YesNo::NO"|enum}"
        />
        <label for="redirect_customer_{$id}"
               class="checkbox"
        >
            <input type="checkbox"
                   name="{$input_name}"
                   id="redirect_customer_{$id}"
                   value="{"YesNo::YES"|enum}"
                   class="cm-switch-availability"
                   {if $redirect_customer}checked{/if}
            />{__("redirect_customer_from_storefront")}
        </label>
    </div>
</div>
