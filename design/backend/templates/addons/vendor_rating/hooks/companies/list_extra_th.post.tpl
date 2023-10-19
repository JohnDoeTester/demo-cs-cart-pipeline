<th width="19%">
    <a class="cm-ajax"
       href="{"`$c_url`&sort_by=absolute_vendor_rating&sort_order=`$search.sort_order_rev`"|fn_url}"
       data-ca-target-id="pagination_contents"
       title="{__("vendor_rating.absolute_vendor_rating")}"
    >
        {__("vendor_rating.absolute_vendor_rating_short")}
        {if $search.sort_by == "absolute_vendor_rating"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}
    </a>
</th>
