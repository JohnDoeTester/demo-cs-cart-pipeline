{if $addons.gdpr.gdpr_cookie_consent !== "Addons\\Gdpr\\CookiesPolicyManager::COOKIE_POLICY_NONE"|enum}
    {$script_attrs = [
        "type" => "text/plain",
        "data-type" => "application/javascript",
        "data-name" => "janrain"
    ] scope=parent}
{/if}