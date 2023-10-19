<select name="export_options[output]" id="output">
    <option value="{"EximOutputOptions::DIRECT_DOWNLOAD"|enum}" {if $value === "EximOutputOptions::DIRECT_DOWNLOAD"|enum}selected="selected"{/if}>{__("direct_download")}</option>
    <option value="{"EximOutputOptions::SCREEN"|enum}" {if $value === "EximOutputOptions::SCREEN"|enum}selected="selected"{/if}>{__("screen")}</option>
    <option value="{"EximOutputOptions::SERVER"|enum}" {if $value === "EximOutputOptions::SERVER"|enum}selected="selected"{/if}>{__("server")}</option>
</select>