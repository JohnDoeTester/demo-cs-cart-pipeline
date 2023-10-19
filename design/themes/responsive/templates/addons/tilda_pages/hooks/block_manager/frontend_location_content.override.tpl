{if $location_data.page_id && $location_data.is_only_content === "YesNo::YES"|enum}
    <div class="tygh-content clearfix {if $layout_data.layout_width != "fixed"}container-fluid {else}container{/if} {$container.user_class}" id="{$smarty.const.TILDA_PAGE_CONTAINER_ID}">
        <link type="text/css" rel="stylesheet" href="{$location_data.tilda_page_upload_settings.css.http_path}/{$smarty.const.TILDA_PAGE_COMMON_STYLE_FILE_NAME}"/>

        {$location_data.description nofilter}
        {$src = "{$location_data.tilda_page_upload_settings.js.http_path}/{$smarty.const.TILDA_PAGE_COMMON_SCRIPT_FILE_NAME}"}

        {hook name="tilda_pages:frontend_container_scripts"}
        {/hook}
        <script
            {$script_attrs|render_tag_attrs nofilter}
            {if !isset($script_attrs["data-src"])}src="{$src}"{/if}
        ></script>
    </div>
{/if}