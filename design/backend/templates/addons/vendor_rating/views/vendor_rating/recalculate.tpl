<div id="rating">
    <table class="table table-responsive">
        <thead>
        <tr>
            <th width="8%">{__("id")}</th>
            <th>{__("vendor")}</th>
            <th width="15%">{__("vendor_rating.absolute_vendor_rating")}</th>
            <th width="35%">&nbsp;</th>
        </tr>
        </thead>
        <tbody>
        {foreach $results as $result}
            <tr class="rating-result rating-result--{if $result->isSuccess()}success{else}errror{/if}">
                <td><a target="_blank" href="{fn_url("companies.update?company_id={$result->getData("company_id")}")}"
                    >{$result->getData("company_id")}</a></td>
                <td><a target="_blank" href="{fn_url("companies.update?company_id={$result->getData("company_id")}")}"
                    >{$result->getData("company_name")}</a></td>
                <td>{$result->getData("rating")}</td>
                <td>
                    {foreach $result->getErrors() as $error}
                        <p>{$error}</p>
                    {/foreach}
                </td>
            </tr>
        {/foreach}
        </tbody>
    </table>
    <!--rating--></div>
