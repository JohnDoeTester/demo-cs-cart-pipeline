{capture name="product_screen"}
    <div class="mockup__product-preview">
        <img src="{$images_dir}/addons/mobile_app/product_preview.gif">
    </div>

    <div class="mockup__product-describes">
        <p class="mockup__product-title darkColor">Mac OS X Lion: The Missing Manual</p>
        <p class="mockup__product-rate">
            {include_ext file="common/icon.tpl" class="fa fa-star fa-lg ratingStarsColor"}
            {include_ext file="common/icon.tpl" class="fa fa-star fa-lg ratingStarsColor"}
            {include_ext file="common/icon.tpl" class="fa fa-star fa-lg ratingStarsColor"}
            {include_ext file="common/icon.tpl" class="fa fa-star fa-lg ratingStarsColor"}
            {include_ext file="common/icon.tpl" class="fa fa-star-half fa-lg ratingStarsColor"}
            <span style="color: #808080">1 reviews</span>
        </p>
        <p class="mockup__product-price darkColor">$34.99</p>
        <p class="mockup__product-desc" style="color: #808080">For a company that promised to "put a pause on new features," Apple sure has been busy-there"s barely a feature left untouched in Mac OS X 10.6 "Snow Leopard."</p>
        <div class="mockup__product-quantity">
            <button class="mockup__product-quantity-btn" disabled>
                {include_ext file="common/icon.tpl" class="icon-minus"}
            </button>
            <span class="mockup__product-quantity-text">1</span>
            <button class="mockup__product-quantity-btn" disabled>
                {include_ext file="common/icon.tpl" class="icon-plus"}
            </button>
        </div>
    </div>

    <div class="mockup__product-tabs tabs">
        <ul class="tabs__container grayColor__background SectionRow__border">
            <li class="tabs__el">Reviews (1)</li>
        </ul>

        <div class="tabs__content tabs__content--review">
            <p>
                <span class="darkColor"><b>David</b></span>
                <span style="float: right;">
                    {include_ext file="common/icon.tpl" class="fa fa-star fa-lg ratingStarsColor"}
                    {include_ext file="common/icon.tpl" class="fa fa-star fa-lg ratingStarsColor"}
                    {include_ext file="common/icon.tpl" class="fa fa-star fa-lg ratingStarsColor"}
                    {include_ext file="common/icon.tpl" class="fa fa-star fa-lg ratingStarsColor"}
                    {include_ext file="common/icon.tpl" class="fa fa-star-half fa-lg ratingStarsColor"}
                </span>
            </p>

            <p class="discussionMessageColor">Lorem ipsum, dolor sit amet consectetur adipisicing elit. Suscipit officiis voluptatum totam repudiandae eligendi iusto magnam cum mollitia corrupti esse, molestiae, cupiditate autem asperiores obcaecati est soluta commodi earum quia.</p>
        </div>
    </div>

    <div class="mockup__product-tabs">
        <ul class="tabs__container grayColor__background SectionRow__border">
            <li class="tabs__el">Features</li>
        </ul>

        <div class="tabs__content tabs__content--features">
            <p style="margin: 0;">
                <span style="color: #595959"><b>Brand</b></span>
                <span style="color: #595959; float: right;">Samsung </span>
            </p>
        </div>
    </div>

    <div class="mockup__product-tabs">
        <ul class="tabs__container grayColor__background SectionRow__border">
            <li class="tabs__el">Vendor</li>
        </ul>

        <div class="tabs__content tabs__content--vendors">
            <p style="position: relative; color: #595959">
                <span><b>CS-Cart</b><br /><span style="font-size: 11px;">245 items</span></span>
                <span style="position: absolute; right: 0; top: 0;" class="buttonWithoutBackgroundTextColor">Details</span>
            </p>
            <p style="color: #595959">The company that makes the best shopping cart software in the world</p>
        </div>
    </div>

    <div class="mockup__product-link-to-store">
        <span class="buttonWithoutBackgroundTextColor">Go to store</span>
    </div>

    <br>
    <br>
    <br>
{/capture}

{include
    file="addons/mobile_app/components/container.tpl"
    content=$smarty.capture.product_screen
    title=__("mobile_app.section.product_screen")
    is_button=true
    uppercase_title=false
    back_icon=true
    navbar_title="Mac OS X Lion: The Missing Manual"
    add_add_button=true
    input_name="product_screen"
}
