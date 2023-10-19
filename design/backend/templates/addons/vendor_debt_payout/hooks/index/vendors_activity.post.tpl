<tr>
    <td class="dashboard-vendors-activity__label">
        {$url = "companies.manage?time_from={$time_from}&time_to={$time_to}&get_suspended=Y"}
        <a href="{$url|fn_url}">
            {__("vendor_debt_payout.dashboard_suspended_vendors")}
        </a>
    </td>
    <td class="dashboard-vendors-activity__value">
        {$dashboard_vendors_activity.suspended_vendors}
    </td>
</tr>
