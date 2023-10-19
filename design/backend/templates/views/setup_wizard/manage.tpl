<div class="setup-wizard-content" id="sw_wizard_container">
    <div class="hidden" id="sw_wizard_subcontainer">
        {include file="views/profiles/components/profiles_scripts.tpl" states=1|fn_get_all_states}

        <div class="wizard-title"><h1>{__("sw.marketplace_setup_wizard")}</h1></div>

        <div class="liquid-slider" id="setup-wizard-main-slider">
            {foreach $setup_wizard as $tab_id => $tab}
                <div class="{$tab_id}" id="sw_{$tab_id}_tab">
                    <h2 class="title hidden">{__($tab.title)}</h2>
                    <div class="head-wrap">
                        <div class="head-text">
                            <h3>{__($tab.header)}</h3>
                        </div>
                    </div>
                    {if $tab.extra}
                        {include file=$tab.extra}
                    {/if}
                    {if $tab.sections && !$tab.show_section_in_extra|default:false}
                        {include file="views/setup_wizard/components/setup_wizard_form.tpl"}
                    {/if}
                <!--sw_{$tab_id}_tab--></div>
            {/foreach}

        </div>
    </div>
<!--sw_wizard_container--></div>

