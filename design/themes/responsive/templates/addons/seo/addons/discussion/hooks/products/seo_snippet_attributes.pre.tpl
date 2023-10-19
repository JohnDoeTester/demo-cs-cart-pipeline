{** FIXME: Deprecated since 4.11.4. Use the "seo_get_schema_org_markup_items_post" PHP hook to extend your product markup **}
{if $product.discussion.search.total_items && $product.discussion.average_rating|floatval}
    <div itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">
        <meta itemprop="reviewCount" content="{$product.discussion.search.total_items}">
        <meta itemprop="ratingValue" content="{$product.discussion.average_rating}">
    </div>
{/if}
{if $product.discussion.posts}
    {foreach $product.discussion.posts as $post}
        {if $post.name && $post.rating_value}
            <div itemprop="review" itemscope itemtype="http://schema.org/Review">
                <div itemprop="author" itemscope itemtype="http://schema.org/Person">
                    <meta itemprop="name" content="{$post.name}" />
                </div>
                <div itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating">
                    <meta itemprop="ratingValue" content="{$post.rating_value}" />
                    <meta itemprop="bestRating" content="5" />
                </div>
            </div>
        {/if}
    {/foreach}
{/if}