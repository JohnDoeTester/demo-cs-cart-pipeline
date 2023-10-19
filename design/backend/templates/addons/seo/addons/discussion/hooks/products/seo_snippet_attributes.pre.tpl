{if $discussion && $discussion.average_rating}

{$stars = $discussion.average_rating|fn_get_discussion_rating}
<p class="nowrap gstars">
    {section name="full_star" loop=$stars.full}{include_ext file="common/icon.tpl" class="gicon-star"}{/section}
    {if $stars.part}{include_ext file="common/icon.tpl" class="gicon-star-half"}{/if}
    {section name="full_star" loop=$stars.empty}{include_ext file="common/icon.tpl" class="gicon-star-empty"}{/section}
</p>
&nbsp;{__("seo.rich_snippets_rating")}: {$discussion.average_rating} - {__("seo.rich_snippets_reviews", [$discussion.search.total_items])} - {/if}‎
