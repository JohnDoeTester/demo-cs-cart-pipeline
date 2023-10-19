{$href = "ebay.get_orders"}

{if $href|fn_check_view_permissions:{$method|default:"GET"}}
    <li>{btn type="list" text={__("get_ebay_orders")} href=$href form="orders_list_form"}</li>
{/if}