<p>{__("advanced_import.modifiers_description") nofilter}</p>
<p>{__("advanced_import.modifiers_self_referencing_info") nofilter}</p>

<ul>
    {foreach from=$modifiers key="name" item="modifier"}
        <li class="advanced-import__modifier-description">
            {__("advanced_import.modifier_description.`$name`") nofilter}
            <ul>
                <li>{__("advanced_import.modifier_number_of_parameters")}: <b>{if $modifier.parameters === null}{__("any")}{else}{$modifier.parameters nofilter}{/if}</b></li>
                {if $modifier.current === null}
                    <li>{__("advanced_import.modifier_self_reference_is_unsupported")}</li>
                {/if}
            </ul>
        </li>
    {/foreach}
</ul>

