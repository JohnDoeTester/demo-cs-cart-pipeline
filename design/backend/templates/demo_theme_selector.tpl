{assign var="c_url" value=$config.current_url|fn_url}

<script>
(function(_, $) {
    $(document).ready(function() {

        $(_.doc).on('click', '#off_minimize_block', function() {
            $('#main_column').removeClass('top-panel-padding');
        });

        $(_.doc).on('click', '#on_minimize_block', function() {
            $('#main_column').addClass('top-panel-padding');
        });

        var open = $.cookie.get('minimize_block');
        if (open) {
            $('#main_column').removeClass('top-panel-padding');
        } else {
            $('#main_column').addClass('top-panel-padding');
        }

        // Countdown timer
        var mins = 30;
        var date = new Date({$smarty.now} * 1000);
        var minutes_left = date.getMinutes() > mins ? 60 - date.getMinutes() : mins - date.getMinutes();
        var seconds = Math.abs(minutes_left * 60 - date.getSeconds());

        var countdownTimer = setInterval(function secondPassed() {
            var elm = $('#timer');
            var minutes = Math.round((seconds - 30)/60);
            var remainingSeconds = seconds % 60;
            if (remainingSeconds < 10) {
                remainingSeconds = "0" + remainingSeconds;
            }
            elm.html(minutes + ":" + remainingSeconds);
            if (seconds == 0) {
                clearInterval(countdownTimer);
            } else {
                seconds--;
            }
        }, 1000);

    });
}(Tygh, Tygh.$));
</script>

{strip}
    <div class="top-panel demo-panel">
        <div id="minimize_block" class="top-panel__wrapper{if $smarty.cookies.minimize_block} hidden{/if}">
            <div class="top-panel__logo">
                <a href="https://www.cs-cart.com/compare.html" class="top-panel__logo-link" target="_blank">{include_ext file="common/icon.tpl" class="glyph-basket top-panel__icon-basket"}</a>
            </div>
            <h4 class="top-panel__title">
                {__("demo_panel.demo_store_panel")}
            </h4>
            <div class="top-panel-action">
                <span class="top-panel-action_item">
                    <span class="top-panel-timer"> {__("demo_panel.demo_will_be_reset_in")} <strong id="timer"></strong> {__("minutes")}</span>
                    {if "ULTIMATE"|fn_allowed_for}
                        {foreach from=$demo_theme.storefronts item=company name=company}
                            <a href="http://{$company.storefront}" class="top-panel-btn">{__("demo_panel.go_storefront")}{if $smarty.foreach.company.total > 1} {$company.company}{/if}</a>
                        {/foreach}
                    {else}
                        <a href="{$config.customer_index}" class="top-panel-btn">{__("demo_panel.go_storefront")}</a>
                    {/if}
                </span>

                <a id="off_minimize_block" class="top-panel__close top-panel-action_item cm-combination-panel cm-save-state cm-ss-reverse">{include_ext file="common/icon.tpl" class="glyph-cancel"}</a>
            </div>
        </div>
        <a id="on_minimize_block" class="minimize-label cm-combination-panel cm-save-state cm-ss-reverse{if !$smarty.cookies.minimize_block} hidden{/if}">
            {include_ext file="common/icon.tpl" class="glyph-down-open minimize-label__icon"}
        </a>
    </div>
{/strip}
