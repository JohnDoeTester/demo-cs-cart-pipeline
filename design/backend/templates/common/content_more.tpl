{*
    $text
    $text_limit
    $display
    $more_text
    $meta
    $prefix
    $visible_comment_lines
*}

{$text_limit = $text_limit|default:600}
{if !$display}
    {$display = ($text|count_characters:true > $text_limit)}
{/if}
{$text = $text|default:"–"}
{$more_text = $more_text|default:__("content_more")}

<div class="cs-content-more" data-ca-elem="contentMore">
    <div class="cs-content-more__text {$meta}"
        data-ca-elem="contentMoreText"
        {if $visible_comment_lines}
            style="--cs-content-more-visible-comment-lines: {$visible_comment_lines};"
        {/if}
    >
        {$prefix nofilter}
        {$text nofilter}
    </div>
    <div class="cs-content-more__btn-wrapper {if !$display}hidden{/if}" data-ca-elem="contentMoreBtnWrapper">
        <button type="button" class="cs-content-more__btn" data-ca-elem="contentMoreBtn">
            {$more_text nofilter}
        </button>
    </div>
</div>
