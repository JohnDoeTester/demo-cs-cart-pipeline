{if $addons.gdpr.gdpr_cookie_consent !== "Addons\\Gdpr\\CookiesPolicyManager::COOKIE_POLICY_NONE"|enum}
    {$script_attrs = [
        "type" => "text/plain",
        "data-src" => $src,
        "data-type" => "application/javascript",
        "data-name" => "tilda_pages"
    ] scope=parent}
{/if}