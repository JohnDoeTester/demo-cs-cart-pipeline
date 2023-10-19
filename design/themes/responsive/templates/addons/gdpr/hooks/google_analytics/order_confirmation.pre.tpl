{if $addons.gdpr.gdpr_cookie_consent !== "Addons\\Gdpr\\CookiesPolicyManager::COOKIE_POLICY_NONE"|enum}
    {$script_attrs = [
        "type" => "text/plain",
        "data-type" => "application/javascript",
        "data-name" => "google_tag_manager"
    ] scope=parent}

    <script {$script_attrs|render_tag_attrs nofilter}>
        window.dataLayer = window.dataLayer || [];
        function gtag() {
            dataLayer.push(arguments);
        }
    </script>
{/if}