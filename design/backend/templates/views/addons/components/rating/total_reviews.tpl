{if $total_addon_reviews > 0}
    <span class="muted">
        <a href="{$addon_reviews_url}"
            target="_blank"
        >
            {$total_addon_reviews} {__("addons.n_reviews", [$total_addon_reviews])}
        </a>
    </span>
{/if}
