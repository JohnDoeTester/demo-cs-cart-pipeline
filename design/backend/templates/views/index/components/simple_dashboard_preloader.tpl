{strip}
{*
    $analytics_preloader_data
*}

{$simple_dashboard_preloader = [
    analytics => [
        title_width => "{__("dashboard.analytics_section_title")|count_characters:true}ch",
        primary_count => 3,
        secondary_count => 3,
        tertiary_count => 3
    ]
]}

<div class="simple-dashboard-preloader">
    <section class="simple-dashboard__section">
        <div class="simple-dashboard-preloader__title"
            style="--dashboard-title-width: {$simple_dashboard_preloader.analytics.title_width};"
        ></div>

        <div class="simple-dashboard-preloader__section-content">
            <div class="simple-dashboard-preloader__column">
                {section loop=$simple_dashboard_preloader.analytics.primary_count name="simple_dashboard_preloader_primary_count"}
                    <div class="simple-dashboard-preloader__block"></div>
                {/section}
            </div>
            <div class="simple-dashboard-preloader__column">
                {section loop=$simple_dashboard_preloader.analytics.secondary_count name="simple_dashboard_preloader_secondary_count"}
                    <div class="simple-dashboard-preloader__block"></div>
                {/section}
            </div>
            <div class="simple-dashboard-preloader__column">
                {section loop=$simple_dashboard_preloader.analytics.tertiary_count name="simple_dashboard_preloader_tertiary_count"}
                    <div class="simple-dashboard-preloader__block"></div>
                {/section}
            </div>
        </div>
    </section>
</div>
{/strip}
