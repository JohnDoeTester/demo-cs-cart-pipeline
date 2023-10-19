{assign var="profile_fields" value=$location|fn_get_profile_fields}

{function name="profile_enter_data_link"}
    <div class="btn-group"><a class="btn cm-dialog-opener" data-ca-target-id="customer_info"  data-ca-scroll="{$scroll_to}">{__("enter_data")}</a></div>
{/function}

{function name="profile_edit_link"}
    {if $is_edit}
    <div class="pull-right">
        <a class="hand cm-tooltip icon-edit cm-dialog-opener{if $click_to} cm-external-click{/if}" data-ca-target-id="customer_info" data-ca-scroll="{$scroll_to}" {if $click_to}data-ca-external-click-id="{$click_to}"{/if} title="{__("edit")}"></a>
    </div>
    {/if}
{/function}

{* billing_address *}
{capture name="billing_address"}
    {hook name="order_management:profile_billing_address"}
    {if !fn_is_empty($user_data)}
        {if $profile_fields.B}
            {if $user_data.b_firstname || $user_data.b_lastname}
                <p class="strong">{$user_data.b_firstname} {$user_data.b_lastname}</p>
            {/if}
            {if $user_data.b_address}
                <p>{$user_data.b_address}</p>
            {/if}
            {if $user_data.b_address_2}
                <p>{$user_data.b_address_2}</p>
            {/if}
            {if $user_data.b_city || $user_data.b_state_descr || $user_data.b_zipcode}
                <p>{$user_data.b_city}{if $user_data.b_city && ($user_data.b_state_descr || $user_data.b_zipcode)},{/if} {$user_data.b_state_descr} {$user_data.b_zipcode}</p>
            {/if}
            {if $user_data.b_country_descr}<p>{$user_data.b_country_descr}</p>{/if}
            {include file="views/profiles/components/profile_fields_info.tpl" fields=$profile_fields.B}
            {if $user_data.b_phone}
                <a href="tel:{$user_data.b_phone}"><bdi>{$user_data.b_phone}</bdi></a>
            {/if}
        {else}
            <p class="muted">{__("no_data")}</p>
        {/if}
    {else}
        <p class="muted">{__("section_is_not_completed")}</p>
        <div class="enter-data">
            {profile_enter_data_link scroll_to="profile_fields_b"}
        </div>
    {/if}
    {/hook}
{/capture}

{* shippng address *}
{capture name="shipping_address"}
    {hook name="order_management:profile_shipping_address"}
    {if !fn_is_empty($user_data)}
        {if $profile_fields.S}
            {if $user_data.s_firstname || $user_data.s_lastname}
                <p class="strong">{$user_data.s_firstname} {$user_data.s_lastname}</p>
            {/if}
            {if $user_data.s_address}
                <p>{$user_data.s_address}</p>
            {/if}
            {if $user_data.s_address_2}
                <p>{$user_data.s_address_2}</p>
            {/if}
            {if $user_data.s_city || $user_data.s_state_descr || $user_data.s_zipcode}
                <p>{$user_data.s_city}{if $user_data.s_city && ($user_data.s_state_descr || $user_data.s_zipcode)},{/if}  {$user_data.s_state_descr} {$user_data.s_zipcode}</p>
            {/if}
            {if $user_data.s_country_descr}<p>{$user_data.s_country_descr}</p>{/if}
            {include file="views/profiles/components/profile_fields_info.tpl" fields=$profile_fields.S}
            {if $user_data.s_phone}
                <a href="tel:{$user_data.s_phone}"><bdi>{$user_data.s_phone}</bdi></a>
            {/if}
            {if $user_data.s_address_type}
                <p>{__("address_type")}: {$user_data.s_address_type}</p>
            {/if}
        {else}
            <p class="muted">{__("no_data")}</p>
        {/if}
    {else}
        <p class="muted">{__("section_is_not_completed")}</p>
        <div class="enter-data">
            {profile_enter_data_link scroll_to="profile_fields_s"}
        </div>
    {/if}
    {/hook}
{/capture}

{* customer information *}

{capture name="customer_information"}
    {if !fn_is_empty($user_data)}
        <div class="profile-info__name">
            {$user_full_name = "`$user_data.firstname` `$user_data.lastname`"|trim}
            {if $user_full_name}
                {if isset($user_data.user_type)
                    && $user_data.user_type !== "UserTypes::CUSTOMER"|enum
                    && $auth.user_type !== "UserTypes::ADMIN"|enum
                    && $user_data.user_id !== $auth.user_id
                    || (
                        !$user_data.user_id
                        && $user_full_name
                    )
                }
                    {$user_full_name}
                {elseif $user_data.user_id}
                    <a href="{"profiles.update?user_id=`$user_data.user_id`"|fn_url}">{$user_full_name}</a>
                {/if}
            {/if}
        </div>
        <div class="profile-info__email">
            <a href="mailto:{$user_data.email}">{$user_data.email}</a>
        </div>

        {if $user_data.ip_address}
            <div class="profile-info__ip">
                <div>{__("ip_address")}:</div>
                <div>{$user_data.ip_address|replace:":":":<wbr>" nofilter}</div>
            </div>
        {/if}
        <div class="clear">
            {if $user_data.phone}
                <div class="profile-info__phone">
                    <span>{__("phone")}:</span>
                    <a href="tel:{$user_data.phone}"><bdi>{$user_data.phone}</bdi></a>
                </div>
            {/if}
            {if $user_data.fax}
                <div class="profile-info__fax">
                    <span>{__("fax")}:</span>
                    <span>{$user_data.fax}</span>
                </div>
            {/if}
            {if $user_data.company}
                <div class="profile-info__company">
                    <span>{__("company")}:</span>
                    <span>{$user_data.company}</span>
                </div>
            {/if}
            {if $user_data.url}
                <div class="profile-info__url">
                    <span>{__("website")}:</span>
                    <span>{$user_data.url}</span>
                </div>
            {/if}
        </div>
        {include file="views/profiles/components/profile_fields_info.tpl" fields=$profile_fields.C customer_info="Y"}
        {if $email_changed}
                <span class="text-warning strong">{__("attention")}</span>
                <span class="text-warning">{__("notice_update_customer_details")}</span>

                <label for="update_customer_details" class="checkbox checkbox--multiline">
                    <input type="checkbox" name="update_customer_details" id="update_customer_details" value="Y"{if $form_id} form={$form_id}{/if} />
                {__("update_customer_info")}</label>
        {/if}

        {if $allow_reselect_customer}
            <div class="enter-data">
                <div class="clearfix shift-button">
                    {include file="pickers/users/picker.tpl" extra_var="order_management.select_customer?page=`$smarty.request.page`" display="radio" but_text=__("change_customer") no_container=true but_meta="btn" shared_force=$users_shared_force}
                </div>
            </div>
        {/if}
    {else}
        <input type="hidden" name="user_data_required" id="selected_user_id" value="{$selected|default:$customer.user_id}"/>
        <div class="mixed-controls">
            <div class="form-inline object-customer-add cm-object-customer-add-container">
                {include file="views/profiles/components/picker/picker.tpl"
                    input_name="customer_data"
                    select_class="cm-object-customer-add"
                    item_ids=[]
                    company_id=$order_info.company_id
                    autofocus=$autofocus
                    url="profiles.get_customer_list?lang_code=`$descr_sl`"
                    dropdown_css_class="select2-dropdown-below-customer-add"
                    users_shared_force=$users_shared_force
                    extra_var="order_management.select_customer?page=`$smarty.request.page`"
                    no_container=true
                }
            </div>
        </div>
        <div class="enter-data">
            {profile_enter_data_link scroll_to="profile_fields_c"}
        </div>
        {if $is_empty_user_data}
        <div class="text-error">
            <label class="hidden cm-required" for="user_data_required">{__("user_data_required")}</label>
            <input type="hidden" id="user_data_required" name="user_data_required" value="" />
        </div>
        {/if}
    {/if}
{/capture}

<div class="sidebar-row">
    {profile_edit_link scroll_to="profile_fields_c"}
    <h6>{__("customer_information")}</h6>
    <div class="profile-info">
        {include_ext file="common/icon.tpl" class="icon-user"}
        {$smarty.capture.customer_information nofilter}
    </div>
</div>
<hr class="profile-info-delim" />

{if $settings.Checkout.address_position == 'shipping_first'}
    <div class="sidebar-row">
        {profile_edit_link scroll_to="profile_fields_s"}
        <h6>{__("shipping_address")}</h6>
        <div class="profile-info">
            {include_ext file="common/icon.tpl" class="icon-truck"}
            {$smarty.capture.shipping_address nofilter}
        </div>
    </div>
    <hr class="profile-info-delim" />
    {if $profile_fields.B}
        <div class="sidebar-row">
            {profile_edit_link scroll_to="profile_fields_b"}
            <h6>{__("billing_address")}</h6>
            <div class="profile-info">
                {include_ext file="common/icon.tpl" class="icon-tag"}
                {$smarty.capture.billing_address nofilter}
            </div>
        </div>
        <hr class="profile-info-delim" />
    {/if}
{else}
    {if $profile_fields.B}
        <div class="sidebar-row">
            {profile_edit_link scroll_to="profile_fields_b"}
            <h6>{__("billing_address")}</h6>
            <div class="profile-info">
                {include_ext file="common/icon.tpl" class="icon-tag"}
                {$smarty.capture.billing_address nofilter}
            </div>
        </div>
    {/if}
    <hr class="profile-info-delim" />
    <div class="sidebar-row">
        {profile_edit_link scroll_to="profile_fields_s"}
        <h6>{__("shipping_address")}</h6>
        <div class="profile-info">
            {include_ext file="common/icon.tpl" class="icon-truck"}
            {$smarty.capture.shipping_address nofilter}
        </div>
    </div>
    <hr class="profile-info-delim" />
{/if}

{hook name="order_management:profiles_info"}
{/hook}
