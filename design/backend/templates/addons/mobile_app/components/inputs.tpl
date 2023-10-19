{*
    $input_name string
    $inputs     array<string|int>
*}
{foreach $inputs as $name => $input}
    <div class="control-group">
        <label class="control-label" for="">{$input.name}:</label>
        <div class="controls">
            <div class="colorpicker">
                <input {if $input.type !== "number"}type="text"{else}type="number"{/if}
                       data-target="{$name}"
                       data-target-input-name="{$input_name}"
                        {if $input.type === "color" || $input.type === "rgba"}
                            data-ca-spectrum-show-alpha="true"
                        {/if}
                       name="m_settings[app_appearance][colors][{$input_name}][{$name}]"
                       id="{$name}"
                       value="{$input.value}"
                        {if $input.type === "color" || $input.type === "rgba"}
                            class="js-mobile-app-input cm-colorpicker"
                        {else}
                            class="js-mobile-app-input"
                        {/if}
                />
            </div>
            <p class="muted description">{$input.description}</p>
        </div>
    </div>
{/foreach}