{if $type && $app["addons.gdpr.service"]->isNeeded($type)}
    {$show_agreement = true scope="parent"}
    {if !$tooltip_only}
        {$input_id = $input_id|default:"gdpr_agreements_{$type}{if $suffix}_{$suffix}{/if}"}
        {$gdpr_target_elem = "{$input_id}_label"}

        <div class="ty-gdpr-agreement {if $hidden}hidden{/if}" data-ca-gdpr-agreement="{$input_id}">
            <label
                for="{$input_id}"
                id="{$input_id}_label"
                class="cm-gdpr-agreement-label {if $agreement_required}cm-gdpr-check-agreement {/if}checkbox ty-gdpr-agreement--label"
                {if $target_node}data-ca-gdpr-target-elem="{$target_node}"{/if}
            >
                <input
                    type="checkbox"
                    id="{$input_id}"
                    name="{$input_name|default:"gdpr_agreements[{$type}]"}"
                    value="{$input_value|default:"Y"}"
                    class="cm-agreement checkbox{if $meta} {$meta}{/if}"
                    {if $onclick}onclick="{$onclick nofilter}"{/if}
                    {if $checked}checked="checked"{/if}
                    data-ca-error-message-target-node="#{$input_id}_error_wrapper"
                />
            </label>
            {capture name="content_for_popup"}
                <div class="ty-gdpr-popup" id="gdpr_popup_{$gdpr_target_elem}">
                    <div class="ty-gdpr-popup-content">
                        {hook name="gdpr:popup_content"}
                        {$app["addons.gdpr.service"]->getFullAgreement($type) nofilter}
                        {/hook}
                    </div>
                </div>
            {/capture}
            {include file="common/popupbox.tpl"
                text=__("gdpr.full_agreement")
                content=$smarty.capture.content_for_popup
                link_text=$app["addons.gdpr.service"]->getShortAgreement($type)
                link_meta=$link_popup_class
                id="gdpr_popup_`$input_id`_label"
            }
            <div id="{$input_id}_error_wrapper"></div>
        </div>

        <script>
            (function(_, $) {
                $.ceFormValidator('registerValidator', {
                    class_name: 'cm-gdpr-check-agreement',
                    message: '{__('gdpr.agreement_required_error')|escape:javascript}',
                    func: function(id) {
                        return $('#' + id).prop('checked');
                    }
                });

                $.ceEvent('on', 'ce.commoninit', function(context) {
                    $(context).find('#{$input_id}').on('change', function (e) {
                        var $item = $(e.target);
                        $.ceEvent('trigger', 'ce.gdpr_agreement_accepted', [$item, context]);
                    });
                });
            }(Tygh, Tygh.$));
        </script>
    {/if}
{/if}
