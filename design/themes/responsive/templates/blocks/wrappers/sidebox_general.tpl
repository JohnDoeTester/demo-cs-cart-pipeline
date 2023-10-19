{if $content|trim}
    <div class="{$sidebox_wrapper|default:"ty-sidebox"}{if isset($hide_wrapper)} cm-hidden-wrapper{/if}{if $hide_wrapper} hidden{/if}{if $block.user_class} {$block.user_class}{/if}{if $content_alignment == "RIGHT"} ty-float-right{elseif $content_alignment == "LEFT"} ty-float-left{/if}">
        <h3 class="ty-sidebox__title cm-combination {if $header_class} {$header_class}{/if}" id="sw_sidebox_{$block.block_id}">
            {hook name="wrapper:sidebox_general_title"}
            {if $smarty.capture.title && $smarty.capture.title|trim}
            <span class="hidden-phone">
                {$smarty.capture.title nofilter}
            </span>
            {else}
                <span class="ty-sidebox__title-wrapper hidden-phone">{$title nofilter}</span>
            {/if}
                {if $smarty.capture.title && $smarty.capture.title|trim}
                    <span class="visible-phone">
                        {$smarty.capture.title nofilter}
                    </span>
                {else}
                    <span class="ty-sidebox__title-wrapper visible-phone">{$title nofilter}</span>
                {/if}
                <span class="ty-sidebox__title-toggle visible-phone">
                    {include_ext file="common/icon.tpl"
                        class="ty-icon-down-open ty-sidebox__icon-open"
                    }
                    {include_ext file="common/icon.tpl"
                        class="ty-icon-up-open ty-sidebox__icon-hide"
                    }
                </span>
            {/hook}
        </h3>
        <div class="ty-sidebox__body" id="sidebox_{$block.block_id}">{$content|default:"&nbsp;" nofilter}</div>
    </div>
{/if}
