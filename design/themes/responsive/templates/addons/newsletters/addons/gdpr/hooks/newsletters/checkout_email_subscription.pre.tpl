{$show_agreement = false}
{$agreement_item_class = "cm-news-subscribe-agreement"}

{capture name="agreement_contents"}
    {include file="addons/gdpr/componentes/agreement_checkbox.tpl"
        type="newsletters_subscribe"
        meta=$agreement_item_class
        suffix=$tab_id
    }
{/capture}

{if $show_agreement}
    {$smarty.capture.agreement_contents nofilter}

    <script>
        (function(_, $) {
            $.ceEvent('on', 'ce.gdpr_agreement_accepted', function ($item, context) {
                if (!$item.hasClass('{$agreement_item_class}')) {
                    return;
                }

                var checked = $item.prop('checked');
                var $other_agreement_items = $('.{$agreement_item_class}').not($item);
                $other_agreement_items.prop('checked', checked);

                var $subscription_items = $('.cm-news-subscribe');

                if (checked) {
                    $subscription_items.prop('disabled', false);
                } else {
                    var $checked_items = $('.cm-news-subscribe:checked');
                    var $first_item = $checked_items.first();

                    // unsubscribe from all newsletters (that were selected on checkout)
                    $checked_items.not($first_item).prop('checked', false);
                    $first_item.click();

                    $subscription_items.prop('disabled', true);
                }
            });

            $.ceEvent('on', 'ce.commoninit', function(context) {
                if ($('.cm-news-subscribe:checked').length) {
                    $('.{$agreement_item_class}').prop('checked', true);
                }

                if ($('.{$agreement_item_class}:checked').length < 1) {
                    $('.cm-news-subscribe').prop('disabled', true);
                }
            });
        }(Tygh, Tygh.$));
    </script>
{/if}



