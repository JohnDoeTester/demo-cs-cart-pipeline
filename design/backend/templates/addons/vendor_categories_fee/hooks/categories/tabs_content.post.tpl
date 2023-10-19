<div id="content_vendor_fee" class="hidden">
    <div class="table-responsive-wrapper">
        <table class="table table-middle table--relative table-responsive">
            <thead class="cm-first-sibling">
            <tr>
                <th width="50%">{__("vendor_plan")}</th>
                <th width="50%">{__("vendor_categories_fee.percent_fee")}</th>
            </tr>
            </thead>
            <tbody>
            {foreach $vendor_plans as $plan}
                {$plan_id = $plan.plan_id}
                {$percent_fee = $category_fee[$plan_id]["percent_fee"]}

                {if $hide_inputs && !isset($percent_fee)}
                    {$percent_fee = $parent_fee[$plan_id]["percent_fee"]}
                {/if}

                <tr>
                    <td data-th="{__("vendor_plan")}">{$plan.plan}</td>
                    <td data-th="{__("vendor_categories_fee.percent_fee")}">
                        <input type="text" name="category_data[vendor_fee][{$plan_id}][percent_fee]" value="{$percent_fee}"{if !isset($percent_fee)} placeholder="{$parent_fee[$plan_id]["percent_fee"]}"{/if}></td>
                    </td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    </div>
</div>
