<th width="9%" class="nowrap">
    <a class="cm-ajax th-text-overflow {if $search.sort_by === "complex_amount"}th-text-overflow--{$search.sort_order_rev}{/if}"
        href="{"`$c_url`&sort_by=amount&sort_order=`$search.sort_order_rev`"|fn_url}"
        data-ca-target-id={$rev}
        title="{__("quantity_long")}"
    >
        {__("quantity")}
    </a>
</th>
