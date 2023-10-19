{$show_agreement = false}

{capture name="agreement_contents"}
    {include file="addons/gdpr/componentes/agreement_checkbox.tpl"
        type="newsletters_subscribe"
        meta="cm-gdpr-newsletters-agreement"
        suffix=$tab_id
    }
{/capture}

{if $show_agreement}
    {$smarty.capture.agreement_contents nofilter}

    <script>
        (function(_, $) {
            $.ceEvent('on', 'ce.gdpr_agreement_accepted', function ($item, context) {
                var checked = $item.prop('checked');
                var $subscription_items = $item.closest('.ty-newsletters').find('input:checkbox').not($item);

                if (checked) {
                    $subscription_items.prop('disabled', false);
                } else {
                    $subscription_items.prop('disabled', true).prop('checked', false);
                }
            });

            $.ceEvent('on', 'ce.commoninit', function(context) {
                $(context).find('.ty-newsletters').find('input:checkbox').not('.cm-gdpr-newsletters-agreement, input:checked').prop('disabled', true);
            });
        }(Tygh, Tygh.$));
    </script>
{/if}


