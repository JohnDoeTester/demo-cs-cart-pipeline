{*
array  $id         Storefront ID
string $name       Storefront Name
bool   $readonly   Whether URL must be displayed as a simple text rather than input
string $input_name Input name
*}

{$input_name = $input_name|default:"storefront_data[name]"}

<div class="control-group">
    <label for="name_{$id}"
           class="control-label cm-required"
    >
        {__("name")}
    </label>
    <div class="controls">
        {if $readonly || $is_forbidden_change_main_info}
            {$name}
            <input type="hidden"
                   name="{$input_name}"
                   value="{$name}"
            />
        {else}
            <input type="text"
                   id="name_{$id}"
                   name="{$input_name}"
                   class="input-large"
                   value="{$name}"
            />
        {/if}
    </div>
</div>
