{if $order_info.shipping}
    {foreach from=$order_info.shipping item="shipping" key="key"}
        {if $shipping.module == 'store_locator' && $addons.store_locator.print_map == 'Y'}
            {if $shipping.store_data}
                <hr/>
                <div>
                    <div>
                    <h2>{__('store_locator.pickup')}</h2>
                    <h2>{$shipping.store_data.name}</h2>
                    <p>
                        {$shipping.store_data.city}<br/>
                        {if $shipping.store_data.pickup_address}{$shipping.store_data.pickup_address}<br/>{/if}
                        {if $shipping.store_data.pickup_phone}<span dir="ltr">{$shipping.store_data.pickup_phone}</span><br/>{/if}
                        {if $shipping.store_data.pickup_time}{$shipping.store_data.pickup_time}<br/>{/if}
                        {if $shipping.store_data.description}{$shipping.store_data.description nofilter}{/if}
                    </p>
                    </div>
                    {if $shipping.store_data.longitude && $shipping.store_data.latitude}
                    <div>
                        <img src="http://static-maps.yandex.ru/1.x/?l=map&ll={$shipping.store_data.longitude|doubleval},{$shipping.store_data.latitude|doubleval}&z=15&size=400,300&pt={$shipping.store_data.longitude|doubleval},{$shipping.store_data.latitude|doubleval},pm2lbl" width="400" height="300" />
                    </div>
                    {/if}
                </div>
                <hr/>
            {/if}
        {/if}
    {/foreach}
{/if}