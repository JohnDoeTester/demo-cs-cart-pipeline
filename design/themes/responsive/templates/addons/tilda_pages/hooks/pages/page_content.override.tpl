{if $page.page_type === $smarty.const.PAGE_TYPE_TILDA_PAGE}
    <div id="{$smarty.const.TILDA_PAGE_CONTAINER_ID}">
        <link type="text/css" rel="stylesheet" href="{$tilda_page_upload_settings.css.http_path}/{$smarty.const.TILDA_PAGE_COMMON_STYLE_FILE_NAME}"/>

        {$page.description nofilter}
    </div>
{/if}