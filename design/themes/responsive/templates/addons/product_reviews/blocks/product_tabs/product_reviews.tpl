{** block-description:product_reviews.title **}
{*
    $product
*}

{component
    name="product_reviews.reviews_on_product_tab"
    product=$product
    request=$smarty.request
    title=__("product_reviews.title")
    quicklink="product_review_link"
    container_id="content_product_reviews_block"
    locate_to_product_review_tab=true
}{/component}
