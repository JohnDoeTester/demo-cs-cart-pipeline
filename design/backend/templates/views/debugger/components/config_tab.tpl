<div class="deb-tab-content" id="DebugToolbarTabConfigContent">
    <div class="deb-sub-tab">
        <ul>
            <li class="active"><a data-sub-tab-id="DebugToolbarSubTabConfigConfig">Config</a></li>
            <li><a data-sub-tab-id="DebugToolbarSubTabConfigSettings">Settings</a></li>
            <li><a data-sub-tab-id="DebugToolbarSubTabConfigRuntime">Runtime</a></li>
        </ul>
    </div>

    <div class="deb-sub-tab-content" id="DebugToolbarSubTabConfigConfig">
        <div class="table-wrapper">
        <table class="deb-table">
            <caption>Config</caption>
            {foreach from=$config|fn_foreach_recursive:"." item="value" key="name"}
                <tr>
                    <td width="200px">{$name}</td>
                    <td>
                        {if gettype($value) == 'boolean'}
                            <pre><code class="php">{if $value}true{else}false{/if}</code></pre>
                        {elseif gettype($value) == 'NULL'}
                            <pre><code class="php">null</code></pre>
                        {elseif gettype($value) == 'object'}
                            <pre><code class="php"><span class="pseudo">Object</span></code></pre>
                        {elseif gettype($value) == 'resource'}
                            <pre><code class="php"><span class="pseudo">Resource</span></code></pre>
                        {else}
                            {$value|strval}
                        {/if}
                    </td>
                </tr>
            {/foreach}
        </table>
        </div>
    </div>

    <div class="deb-sub-tab-content" id="DebugToolbarSubTabConfigSettings">
        <div class="table-wrapper">
        <table class="deb-table">
            <caption>Settings</caption>
            {foreach from=$settings|fn_foreach_recursive:"." item="value" key="name"}
                <tr>
                    <td width="200px">{$name}</td>
                    <td>
                        {if gettype($value) == 'boolean'}
                            <pre><code class="php">{if $value}true{else}false{/if}</code></pre>
                        {elseif gettype($value) == 'NULL'}
                            <pre><code class="php">null</code></pre>
                        {elseif gettype($value) == 'object'}
                            <pre><code class="php"><span class="pseudo">Object</span></code></pre>
                        {elseif gettype($value) == 'resource'}
                            <pre><code class="php"><span class="pseudo">Resource</span></code></pre>
                        {else}
                            {$value|strval}
                        {/if}
                    </td>
                </tr>
            {/foreach}
        </table>
        </div>
    </div>
    
    <div class="deb-sub-tab-content" id="DebugToolbarSubTabConfigRuntime">
        <div class="table-wrapper">
        <table class="deb-table">
            <caption>Runtime</caption>
            {foreach from=$data.runtime|fn_foreach_recursive:"." item="value" key="name"}
                <tr>
                    <td width="200px">{$name}</td>
                    <td>
                        {if gettype($value) == 'boolean'}
                            <pre><code class="php">{if $value}true{else}false{/if}</code></pre>
                        {elseif gettype($value) == 'NULL'}
                            <pre><code class="php">null</code></pre>
                        {elseif gettype($value) == 'object' || $value === 'object'}
                            <pre><code class="php"><span class="pseudo">Object</span></code></pre>
                        {elseif gettype($value) == 'resource' || $value === 'resource'}
                            <pre><code class="php"><span class="pseudo">Resource</span></code></pre>
                        {else}
                            {$value|strval}
                        {/if}
                    </td>
                </tr>
            {/foreach}
        </table>
        </div>
    </div>
<!--DebugToolbarTabConfigContent--></div>