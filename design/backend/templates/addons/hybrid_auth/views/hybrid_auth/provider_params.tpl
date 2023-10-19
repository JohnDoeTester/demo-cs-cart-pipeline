<div id="content_params_{$id}">
    {foreach $providers_schema[$provider].params as $param_id => $param}
        {if $param.type === "input"}
            <div class="control-group">
                <label for="section_{$param_id}_{$id}" class="control-label{if $param.required} cm-required{/if}">{__($param.label)}:</label>
                <div class="controls">
                    <input type="text" name="provider_data[params][{$param_id}]" size="30" value="{$provider_data['params'][$param_id]|default:$param.default}" id="section_{$param_id}_{$id}">
                </div>
            </div>

        {elseif $param.type === "checkbox"}
            <div class="control-group">
                <label for="section_{$param_id}_{$id}" class="control-label{if $param.required} cm-required{/if}">{__($param.label)}:</label>
                <div class="controls">
                    <input type="hidden" name="provider_data[params][{$param_id}]" value="{"YesNo::NO"|enum}" />
                    <input type="checkbox" name="provider_data[params][{$param_id}]" value="{"YesNo::YES"|enum}" id="section_{$param_id}_{$id}"
                        {if (!isset($provider_data['params'][$param_id]) && $param.default === "YesNo::YES"|enum) || (isset($provider_data['params'][$param_id]) && $provider_data['params'][$param_id] === "YesNo::YES"|enum)}checked="checked"{/if}>
                </div>
            </div>

        {elseif $param.type === "select"}
            <div class="control-group">
                <label for="section_{$param_id}_{$id}" class="control-label{if $param.required} cm-required{/if}">{__($param.label)}:</label>
                <div class="controls">
                    <select name="provider_data[params][{$param_id}]" id="section_{$param_id}_{$id}">
                        {foreach $param.options as $value => $option}
                        <option value={$value} {if $value === $provider_data['params'][$param_id]|default:$param.default}selected="selected"{/if}>{__($option)}</option>
                        {/foreach}
                    </select>
                    <p class="muted description">{__($param.tooltip) nofilter}</p>
                </div>
            </div>
        {/if}
    {/foreach}
<!--content_params_{$id}--></div>
