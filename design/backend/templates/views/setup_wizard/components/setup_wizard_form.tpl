<div id="setup_wizard_{$tab_id}_form">
    <form name="setup_wizard_{$tab_id}_form_elm" class="form-horizontal cm-ajax cm-ajax-force" action="{""|fn_url}" method="post" enctype="multipart/form-data">
        <input type="hidden" name="dispatch" value="setup_wizard.update" />
        <input type="hidden" name="result_ids" value="setup_wizard_{$tab_id}_form" />

        {foreach $tab.sections as $sect_id => $sect}
            <div class="sw-columns-block">
                {if $sect.template}
                    {include file=$sect.template}
                    </div>
                    {continue}
                {/if}
                {if $sect.header}
                    <div class="control-group">
                        <h2 class="sw-block-title">{__("{$sect.header}")}</h2>
                    </div>
                {/if}
                {if $sect.show_submit_button === "YesNo::YES"|enum}
                    <div class="pull-right">
                        <button class="btn btn-primary btn-large ladda-button" type="submit" data-style="slide-right"><span class="ladda-label">{__("sw.activate")}</span></button>
                        <div class="sw-notifications-box"></div>
                    </div>
                {/if}
                {if $sect.decoration_class}
                    <div class="{$sect.decoration_class}"></div>
                {/if}
                {if $sect.hidden_items}
                    <div class="sw-toggle-hidden" ><a id="sw_sect_{$tab_id}_{$sect_id}" href="#" class="cm-combination">{__($sect.show_hidden_text)}</a></div>
                {/if}
                {foreach $sect.items as $item}
                    {include file="views/setup_wizard/components/settings_fields.tpl" class="`$item.decoration_class`" item=$item.setting_data html_id="sw_`$item.setting_data.name`" html_name="settings[`$item.setting_data.name`]" label_extra=$item.label_extra placeholder=$item.placeholder field_extra_description=$item.field_extra_description field_extra_link=$item.field_extra_link}
                {/foreach}
                <div id="sect_{$tab_id}_{$sect_id}" class="hidden">
                    {foreach $sect.hidden_items  as $item}
                        {include file="views/setup_wizard/components/settings_fields.tpl" class="`$item.decoration_class`" item=$item.setting_data html_id="sw_`$item.setting_data.name`" html_name="settings[`$item.setting_data.name`]"}
                    {/foreach}
                </div>
            </div>
        {/foreach}

        {if $tab.show_submit_button !== "YesNo::NO"|enum}
            <div class="apply-button">
                <button class="btn btn-primary btn-large ladda-button" type="submit" data-style="slide-right"><span class="ladda-label">{__("save")}</span></button>
                <span class="sw-notifications-box"></span>
            </div>
        {/if}
    </form>
<!--setup_wizard_{$tab_id}_form--></div>