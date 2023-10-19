{capture name="mainbox"}

{include file="views/profiles/components/profiles_scripts.tpl"}

<form action="{""|fn_url}" method="post" name="cities_form" class="{if $runtime.company_id} cm-hide-inputs{/if}">
<input type="hidden" name="country_code" value="{$search.country}" />
<input type="hidden" name="state_code" value="{$search.state_code}" />

{include file="common/pagination.tpl" save_current_page=true save_current_url=true}

{if $cities}
    <table width="100%" class="table table-middle table--relative">
    <thead>
    <tr>
        {if $smarty.request.extended}
            <th width="1%">{include file="common/check_items.tpl"}</th>
        {/if}
        {hook name="cities:table_head"}
        <th width="20%">{__("city")}</th>
        <th width="20%">{__("country")}/{__("state")}</th>
        {/hook}
        {if $smarty.request.extended}
            <th width="5%">&nbsp;</th>
        {/if}
    </tr>
    </thead>
    {foreach from=$cities item=city}
        <input type="hidden" name="cities[{$city.city_id}][country_code]" value="{$city.country_code}"/>
        <input type="hidden" name="cities[{$city.city_id}][state_code]" value="{$city.state_code}"/>
        <tr>
            {if $smarty.request.extended}
            <td>
                <input type="checkbox" name="city_ids[]" value="{$city.city_id}" class="cm-item" />
            </td>
            {/if}
            {hook name="cities:table_content"}
            <td>
                <label>{$city.city}</label>
            </td>
            <td>
                <span class="muted"><small>{$city.country_name}</small></span>
                </br>
                <span class="muted"><small>{$city.state_name}</small></span>
            </td>
            {/hook}
            {if $smarty.request.extended}
                <td class="nowrap">
                    {capture name="tools_list"}
                        {assign var="return_current_url" value=$config.current_url|escape:url}
                        <li>{btn type="list" class="cm-confirm cm-post" text=__("delete") href="cities.delete?city_id=`$city.city_id`&redirect_url=`$return_current_url`"}</li>
                    {/capture}
                    <div class="hidden-tools">
                        {dropdown content=$smarty.capture.tools_list}
                    </div>
                </td>
            {/if}
        </tr>
    {/foreach}
    </table>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl"}

</form>

{capture name="tools"}
    {capture name="add_new_picker"}

    <form action="{""|fn_url}" method="post" name="add_cities_form" class="form-horizontal form-edit">
    <input type="hidden" name="city_data[state_code]" value="{$search.state_code}" />
    <input type="hidden" name="city_data[country_code]" value="{$search.country_code}" />
    <input type="hidden" name="city_id" value="0" />

    {foreach from=$countries item="country" key="code"}
        {if $code == $search.country_code}
            {assign var="title" value="{__("new_city")} (`$country`)"}
            {assign var="country_name" value=$country}
        {/if}
    {/foreach}

    {assign var="_country" value=$search.country_code|default:$settings.Checkout.default_country}
    {foreach from=$states.$_country item="state"}
        {if $state.code == $search.state_code}
            {assign var="title" value="`$title` (`$state.state`)"}
            {assign var="state_name" value=$state.state}
        {/if}
    {/foreach}


    <div class="cm-j-tabs">
        <ul class="nav nav-tabs">
            <li id="tab_new_cities" class="cm-js active"><a>{__("general")}</a></li>
        </ul>
    </div>

    <div class="cm-tabs-content">
    <fieldset>
        {hook name="cities:city_content"}
        <div class="control-group">
            <label class="cm-required control-label" for="elm_city_name">{__("city")}:</label>
            <div class="controls">
            <input type="text" id="elm_city_name" name="city_data[city]" size="55" value="" />
            </div>
        </div>

        {assign var="for_name_country" value=$search.country_code}
        {assign var="for_name_state" value=$search.state_code}
        <div class="control-group">
            <label class="cm-required control-label" for="elm_countries_new">{__("country")}:</label>
            <div class="controls">
                    {$country_name}
            </div>
        </div>

        <div class="control-group">
            <label id="elm_states" class="cm-required control-label" for="elm_states_new">{__("state")}:</label>
            <div class="controls">
                    {$state_name}
            </div>
        </div>
        {/hook}
    </fieldset>
    </div>

    <div class="buttons-container">
        {include file="buttons/save_cancel.tpl" create=true but_name="dispatch[cities.update]" cancel_action="close"}
    </div>

</form>

{/capture}
{/capture}

{if $smarty.request.extended}
{capture name="buttons"}
    {capture name="tools_list"}
        {if $cities}
            <li>{btn type="delete_selected" dispatch="dispatch[cities.m_delete]" form="cities_form"}</li>
        {/if}
    {/capture}
    {dropdown content=$smarty.capture.tools_list}
{/capture}
{/if}

{if ($_REQUEST.state_code)}
	{capture name="adv_buttons"}
		{include file="common/popupbox.tpl" id="new_city" action="cities.add" text=$title content=$smarty.capture.add_new_picker title=__("add_city") act="general" icon="icon-plus"}
	{/capture}
{/if}


{capture name="sidebar"}
<div class="sidebar-row">
<h6>{__("admin_search_title")}</h6>

<form action="{""|fn_url}" name="cities_filter_form" method="get">
<div class="sidebar-field">

	{assign var="_country" value=$search.country_code|default:$settings.Checkout.default_country}
    <label>{__("country")}:</label>
		<select name="country_code" class="cm-country cm-location-states" id="elm_countries">
			<option value="">- {__("select_country")} -</option>
			{foreach from=$countries item="country" key="code"}
				<option {if $code == $_country}selected="selected"{/if} value="{$code}">{$country}</option>
			{/foreach}
		</select>

    {assign var="_state" value=$_REQUEST.state_code}
    <label id="elm_states_lbl" {if empty($states.$_country)}class="hidden"{/if}>{__("state")}:</label>
		<select name="state_code" class="cm-state cm-location-states" id="sd_elm_states">
			<option value="">- {__("select_state")} -</option>
			{if $states && $states.$_country}
				{foreach from=$states.$_country item="state"}
					<option {if $_state == $state.code}selected="selected"{/if} value="{$state.code}">{$state.state}</option>
				{/foreach}
			{/if}
		</select>
		<div class="hidden"><input type="text" id="sd_elm_states_d" name="state_code" size="32" maxlength="64" value="{$_state}" disabled="disabled" readonly="readonly" class="cm-state cm-location-states input-large hidden cm-skip-avail-switch" style="border:0px; background-color: transparent;"/></div>
        <span id="elm_states_empty" {if !empty($states.$_country)}class="hidden"{/if}>{__("empty_state")}</br><a href="admin.php?dispatch=states.manage">{__("new_city_state")}</a></span>
</div>
    {include file="buttons/search.tpl" but_name="dispatch[cities.manage]"}
    <hr/>
    {__("select_state_instruction")}

</form>
</div>

<script>
//<![CDATA[
(function(_, $) {

    $(document).ready(function() {
        $(_.doc).on('change', 'select.cm-country', function() {
            var inp = $('input.cm-state');
            if (!inp.hasClass('hidden')) {
                $('#elm_states_lbl').addClass('hidden');
                $('input.cm-state').val('');
                $('#elm_states_empty').removeClass('hidden');
            } else {
                $('#elm_states_lbl').removeClass('hidden');
                $('#elm_states_empty').addClass('hidden');
            }
        });
    });

}(Tygh, Tygh.$));
//]]>
</script>

{/capture}


{/capture}
{include file="common/mainbox.tpl" title=__("cities") content=$smarty.capture.mainbox adv_buttons=$smarty.capture.adv_buttons buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar select_languages=true}
