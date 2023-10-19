{*
array  $id         Storefront ID
string $url        Storefront URL
bool   $readonly   Whether URL must be displayed as a simple text rather than input
string $input_name Input name
*}

{$input_name = $input_name|default:"storefront_data[url]"}

<div class="control-group">
    <label for="url_{$id}"
           class="control-label cm-required"
    >{__("storefront_url")}</label>
    <div class="controls">
        {if $readonly || $is_forbidden_change_main_info}
            {$url|puny_decode}
            <input type="hidden"
                   name="{$input_name}"
                   value="{$url|puny_decode}"
            />
        {else}
            <input type="text"
                   id="url_{$id}"
                   name="{$input_name}"
                   class="input-large"
                   value="{$url|puny_decode}"
            />
        {/if}
    </div>
</div>
