{*
    $meta                           string                  Block class
    $show_header                    bool                    Show block header
    $enable_popover                 bool                    Enable popover
    $category_id                    string                  Product category ID
    $category_tabindex              number                  Category tabindex
    $feature                        array                   Feature data
    $form_id                        string                  Quick form unique ID
    $event_id                       string                  Js event id that will be fired after feature created
*}

{$show_header = $show_header|default:true}
{$enable_popover = $enable_popover|default:true}
{$form_id = $form_id|default:uniqid()}
{$action_context = $action_context|default:$smarty.request._action_context}

<div class="vendor-plan-create__block {$meta} {if $enable_popover}well{/if}">
    {if $show_header}
        <div class="vendor-plan-create__header">
            <h4 class="subheader vendor-plan-create__subheader">{__("vendor_plans.new_vendor_plan")}</h4>
            {if $enable_popover}
                <button type="button" class="close flex-vertical-centered cm-inline-dialog-closer" data-ca-vendor-plan-create-elem="close">
                    {include_ext file="common/icon.tpl" class="icon-remove"}
                </button>
            {/if}
        </div>
    {/if}
    <form action="{""|fn_url}"
        method="post"
        class="cm-ajax"
        name="update_vendor_plan_form_{$form_id}"
        id="update_vendor_plan_form_{$form_id}"
        class="form-horizontal form-edit" enctype="multipart/form-data"
        {if $action_context}data-ca-ajax-done-event="ce.{$action_context}.vendor_plan_save"{/if}
    >

        {* Vendor plan name *}
        <div class="control-group">
            <label class="control-label cm-required" for="elm_plan_name_{$form_id}">{__("name")}</label>
            <div class="controls">
                <input id="elm_plan_name_{$form_id}"
                    class="input-large"
                    type="text"
                    name="plan_data[plan]"
                    value="{$plan.plan|default:""}"
                />
            </div>
        </div>
        {* /Vendor plan name *}

        {* Vendor plan price periodicity *}
        <div class="control-group vendor-plan-create__periodicity">
            <label class="control-label" for="elm_price_{$form_id}">{__("price")} ({$currencies.$primary_currency.symbol nofilter}):</label>
            <div class="controls">
                <input type="text" id="elm_price_{$form_id}" name="plan_data[price]" size="10" value="" class="input-text-short" />
                <select name="plan_data[periodicity]" class="input-small">
                    {foreach $periodicities as $key => $item}
                        <option value="{$key}">{$item}</option>
                    {/foreach}
                </select>
            </div>
        </div>
        {* /Vendor plan price periodicity *}

        {* Vendor plan comission *}
        <div class="control-group">
            <label class="control-label" for="elm_commission_{$form_id}">{__("vendor_plans.transaction_fee")}:</label>
            <div class="controls">
                <input id="elm_commission_{$form_id}" type="text" name="plan_data[commission]" class="input-mini" value="" size="4">
                <span class="control-text">&nbsp;%&nbsp;+&nbsp;</span>
                <input type="text" name="plan_data[fixed_commission]" value="" class="input-mini" size="4">
                <span class="control-text">{$currencies.$primary_currency.symbol nofilter}</span>
            </div>
        </div>
        {* /Vendor plan comission *}

        {* Vendor plan status*}
        {include file="common/select_status.tpl" input_name="plan_data[status]" id="plan_data_`$form_id`" obj=$plan hidden=true}
        {* /Vendor plan status*}

        <div class="vendor-plan-create__footer">
            {btn type="text"
                id="advanced_vendor_plan_creation"
                text=__("vendor_plans.advanced_vendor_plan_creation")
                title=__("vendor_plans.advanced_vendor_plan_creation")
                href="{"vendor_plans.add"|fn_url}"
                class="btn cm-dialog-opener cm-dialog-destroy-on-close"
                target_id="add_vendor_plan_popup"
                data=[
                    "data-ca-target-id" => "add_vendor_plan_popup",
                    "data-ca-dialog-content-request-form" => "update_vendor_plan_form_{$form_id}",
                    "data-ca-dialog-action-context" => $action_context
                ]
            }
            {include file="buttons/button.tpl" but_role="submit" but_text=__("create") but_name="dispatch[vendor_plans.update]"}
        </div>
    </form>
</div>
