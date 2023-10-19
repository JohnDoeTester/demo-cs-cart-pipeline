{if $locations}
    <ul class="ty-store-locator__geolocation__locations">
        {foreach $locations as $country_id => $country}
            <li class="ty-store-locator__geolocation__location__country">
                <h3 class="ty-store-locator__geolocation__location__country__title">{$country.title}</h3>
                <ul class="ty-store-locator__geolocation__location__states">
                    {foreach $country.states as $state_id => $state}
                        <li class="ty-store-locator__geolocation__location__state">
                            <h4 class="ty-store-locator__geolocation__location__state__title">{$state.title}</h4>
                            <ul class="ty-store-locator__geolocation__location__cities">
                                {foreach $state.cities as $city}
                                    <li class="ty-store-locator__geolocation__location__city">
                                        <a href="#"
                                           data-ca-store-locator-location-element="city"
                                           data-ca-store-locator-location-city="{$city}"
                                           data-ca-store-locator-location-state="{$state_id}"
                                           data-ca-store-locator-location-state-name="{$state.title}"
                                           data-ca-store-locator-location-country="{$country_id}"
                                           data-ca-store-locator-location-country-name="{$country.title}"
                                           class="cm-dialog-closer"
                                        >{$city}</a>
                                    </li>
                                {/foreach}
                            </ul>
                        </li>
                    {/foreach}
                </ul>
            </li>
        {/foreach}
    </ul>
{/if}
