{*
    $product_reviews                array                               Product reviews
    $product_review                 array                               Product review
    $show_product                   bool                                Show product
    $product_reviews_search         array                               Product reviews search
    $c_url                          string                              Current URL
    $rev                            string                              Rev
*}

{$show_product = $show_product|default:true}
{$search = $product_reviews_search}
{$c_url=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
{$rev=$smarty.request.content_id|default:"pagination_product_reviews"}

<form action="{""|fn_url}" method="post" name="product_reviews_form" id="product_reviews_form">

{include file="common/pagination.tpl"
    save_current_page=true
    save_current_url=true
    div_id=$rev
    search=$product_reviews_search
}

    {if $product_reviews}
        {capture name="product_reviews_table"}
            <table width="100%" class="table table-middle table--relative table-responsive table--overflow-hidden longtap-selection">
                <thead
                        data-ca-bulkedit-default-object="true"
                        data-ca-bulkedit-component="defaultObject"
                >
                    <tr>
                        <th class="center mobile-hide table__check-items-column table__check-items-column--disabled">
                            <input type="checkbox"
                                   class="bulkedit-toggler hide"
                                   data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                                   data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                            />
                        </th>
                        {if $show_product}
                            <th width="10%"></th>
                        {/if}
                        <th width="43%">
                            <div class="th-text-overflow-wrapper">
                                {include file="common/table_col_head.tpl"
                                    type="product_review_id"
                                    text=__("id")
                                    class="th-text-overflow--width-auto"
                                }
                                {include file="common/table_col_head.tpl"
                                    type="rating_value"
                                    text=__("product_reviews.rating")
                                }
                                {include file="common/table_col_head.tpl" text=__("message")}
                                {if $show_product}
                                    {include file="common/table_col_head.tpl" text=__("product")}
                                {/if}
                                {include file="common/table_col_head.tpl" text=__("customer")}
                            </div>
                        </th>
                        <th width="13%">
                            {include file="common/table_col_head.tpl"
                                type="helpfulness"
                                text=__("product_reviews.helpfulness")
                            }
                        </th>
                        <th width="10%">
                            {include file="common/table_col_head.tpl" text=__("status")}
                        </th>
                        <th width="9%" class="mobile-hide">
                            {include file="common/table_col_head.tpl" text="&nbsp;"}
                        </th>
                        <th width="15%">
                            {include file="common/table_col_head.tpl" type="product_review_timestamp" text=__("date")}
                        </th>
                    </tr>
                </thead>
                {foreach $product_reviews as $product_review}
                    {include file="addons/product_reviews/views/product_reviews/components/manage/review_row.tpl"
                        product_review=$product_review
                        show_product=$show_product
                        rev=$rev
                    }
                {/foreach}
            </table>
        {/capture}

        {include file="common/context_menu_wrapper.tpl"
            form='product_reviews_form'
            object="product_reviews"
            items=$smarty.capture.product_reviews_table
        }
    {else}
        <p class="no-items">{__("no_data")}</p>
    {/if}

{include file="common/pagination.tpl"
    save_current_page=true
    save_current_url=true
    div_id=$rev
    search=$product_reviews_search
}
</form>
