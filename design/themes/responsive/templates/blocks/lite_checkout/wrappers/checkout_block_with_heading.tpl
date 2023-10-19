{if $content|trim}
    {if $block.user_class || $content_alignment == 'RIGHT' || $content_alignment == 'LEFT'}
        <div class="{if $block.user_class}{$block.user_class}{/if} {if $content_alignment == 'RIGHT'}ty-float-right{elseif $content_alignment == 'LEFT'}ty-float-left{/if}">
    {/if}
        <div class="litecheckout__container">
            <div class="litecheckout__group">
                <div class="litecheckout__item">
                    <h2 class="litecheckout__step-title">{$block.name}</h2>
                </div>
            </div>
            {$content nofilter}
        </div>
    {if $block.user_class || $content_alignment == 'RIGHT' || $content_alignment == 'LEFT'}
        </div>
    {/if}
{/if}