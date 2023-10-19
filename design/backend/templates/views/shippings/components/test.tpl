
<div id="elm_shipping_test">
{if $service}
    <div class="table-wrapper">
        <table width="100%" class="table">
        <thead>
        <tr>
            <th>&nbsp;</th>
            <th>{__("origination")} </th>
            <th>&nbsp;&nbsp;&nbsp;</th>
            <th>{__("destination")} </th>
        </tr>
        </thead>

        <tbody>
        <tr class="table-row">
            <td><span>{__("address")}:</span>&nbsp;</td>
            <td>{$settings.Company.company_address} </td>
            <td>&nbsp;&nbsp;&nbsp;</td>
            <td>{$settings.Checkout.default_address} </td>
        </tr>
        <tr>
            <td><span>{__("city")}:</span>&nbsp;</td>
            <td>{$settings.Company.company_city}</td>
            <td>&nbsp;&nbsp;&nbsp;</td>
            <td>{$settings.Checkout.default_city} </td>
        </tr>
        <tr class="table-row">
            <td><span>{__("country")}:</span>&nbsp;</td>
            <td>{$settings.Company.company_country}</td>
            <td>&nbsp;&nbsp;&nbsp;</td>
            <td>{$settings.Checkout.default_country} </td>
        </tr>
        <tr>
            <td><span>{__("state")}:</span>&nbsp;</td>
            <td>{$settings.Company.company_state}</td>
            <td>&nbsp;&nbsp;&nbsp;</td>
            <td>{$settings.Checkout.default_state} </td>
        </tr>
        <tr class="table-row">
            <td><span>{__("zip_postal_code")}:</span>&nbsp;</td>
            <td>{$settings.Company.company_zipcode}</td>
            <td>&nbsp;&nbsp;&nbsp;</td>
            <td>{$settings.Checkout.default_zipcode} </td>
        </tr>
        </tbody>
        </table>
    </div>

    <div class="table-wrapper">
        <table width="100%" class="table-middle">
        <tbody>
        <tr>
            <td width="15%"><strong>{__("shipping_service")}:</strong></td>
            <td>{$service}</td>
        </tr>
        <tr>
            <td><strong>{__("weight")}:</strong></td>
            <td>{$weight}&nbsp;{$settings.General.weight_symbol}</td>
        </tr>
        {if $data.price !== false}
            <tr>
                <td><strong>{__("cost")}:</strong></td>
                <td>{include file="common/price.tpl" value=$data.price}</td>
            </tr>
            {if $data.service_delivery_time|trim}
                <tr>
                    <td><strong>{__("delivery_time")}:</strong></td>
                    <td>{$data.service_delivery_time}</td>
                </tr>
            {/if}
        {else}
            <tr>
                <td width="150px"><strong>{__("error")}:</strong></td>
                <td width="300px"><span>{if $data.error}{$data.error}{else}n/a{/if}</span></td>
            </tr>
        {/if}
        </tbody>
        </table>
    </div>
{/if}
<!--elm_shipping_test--></div>
