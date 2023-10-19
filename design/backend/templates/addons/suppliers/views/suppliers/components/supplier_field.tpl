{if $runtime.company_id && !$company_id}
    {$company_id = $runtime.company_id}
{/if}

{$result_ids = "content_detailed" scope="root"}

{$supplier = fn_if_get_supplier($selected, $company_id)}

{if $supplier !== false}

    {capture name="s_body"}
        {if $read_only}
            {$supplier.name}
        {else}
            {include file="addons/suppliers/views/suppliers/components/picker/picker.tpl" 
                input_name=$name
                item_ids=[$supplier.supplier_id]
                meta="span3"
            }
        {/if}
    {/capture}

    {if !$no_wrap}
        <div class="control-group" id="suppliers_selector">
            <label class="control-label" for="{$id|default:"supplier_id"}">{__("supplier")}</label>
            <div class="controls">
                {$smarty.capture.s_body nofilter}
                {if $tooltip}
                    <p class="muted description">{$tooltip nofilter}</p>
                {/if}
            </div>
        <!--suppliers_selector--></div>
    {else}
        {$smarty.capture.c_body nofilter}
    {/if}

{/if}