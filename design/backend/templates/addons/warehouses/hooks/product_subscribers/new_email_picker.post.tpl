{if $destinations}
    <div class="control-group">
        <label for="users_destination" class="control-label">{__("rate_area")}</label>
        <div class="controls">
            <select name="add_subscriber[destination_id]" id="users_destination" class="input-medium">
                <option> -- </option>
                {foreach $destinations as $destination}
                    <option value="{$destination['destination_id']}">{$destination['destination']}</option>
                {/foreach}
            </select>
        </div>
    </div>
{/if}