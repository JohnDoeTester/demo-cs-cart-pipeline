{*
array  $id             Storefront ID
bool   $readonly       Whether URL must be displayed as a simple text rather than input
string $input_name     Input name
string $theme          Selected theme
string  $current_theme Selected theme name
string  $current_style Selected theme style name
string  $theme_url     Theme selection URL
*}

{$input_name = $input_name|default:"storefront_data[theme_name]"}
{$theme_url = $theme_url|default:"themes.manage?s_storefront={$id}"}

{if $id}
    <div class="control-group">
        <label for="theme_{$id}"
               class="control-label"
        >
            {__("store_theme")}
        </label>
        <div class="controls">
            <input type="hidden"
                   name="{$input_name}"
                   value="{$theme}"
            />
            <p>{$current_theme}: {$current_style}</p>
            {if !$readonly}
                <a href="{fn_url($theme_url)}">{__("goto_theme_configuration")}</a>
            {/if}
        </div>
    </div>
{else}
    <input type="hidden"
           name="{$input_name}"
           value="{$theme}"
    />
{/if}
