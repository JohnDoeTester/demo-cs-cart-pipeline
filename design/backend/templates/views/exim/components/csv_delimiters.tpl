<select name="{$name}" {if $id}id="{$id}"{/if}>
    {if $allow_auto_detect}
        <option value="{"Addons\AdvancedImport\CsvDelimiters::AUTO"|enum}"{if $value == "Addons\AdvancedImport\CsvDelimiters::AUTO"|enum} selected="selected"{/if}>{__("auto")}</option>
    {/if}
    <option value="S" {if $value == "S"}selected="selected"{/if}>{__("semicolon")}</option>
    <option value="C" {if $value == "C"}selected="selected"{/if}>{__("comma")}</option>
    <option value="T" {if $value == "T"}selected="selected"{/if}>{__("tab")}</option>
</select>
