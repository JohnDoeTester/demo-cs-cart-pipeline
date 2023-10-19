{capture name="main"}
    <div class="mockup__carousel-container">
        <img src="{$images_dir}/addons/mobile_app/king.jpg" class="mockup__carousel-img"/>
    </div> 
    
    <div class="categoriesBackgroundColor__background" style="margin-left: -10px; margin-right: -10px; padding: 10px 10px;">
        <div class="mockup__category-container">
            <div class="mockup__category-item categoryBlockBackgroundColor__background categoryBorderRadius">
                <p class="mockup__category-name categoryBlockTextColor">Electronics</p>
            </div>
            <div class="mockup__category-item categoryBlockBackgroundColor__background categoryBorderRadius">
                <p class="mockup__category-name categoryBlockTextColor">Computers</p>
            </div>
            <div class="mockup__category-item categoryBlockBackgroundColor__background categoryBorderRadius">
                <p class="mockup__category-name categoryBlockTextColor">Sports & Outdoors</p>
            </div>
            <div class="mockup__category-item categoryBlockBackgroundColor__background categoryBorderRadius">
                <p class="mockup__category-name categoryBlockTextColor">Apparel</p>
            </div>
            <div class="mockup__category-item categoryBlockBackgroundColor__background categoryBorderRadius">
                <p class="mockup__category-name categoryBlockTextColor">Books</p>
            </div>
            <div class="mockup__category-item categoryBlockBackgroundColor__background categoryBorderRadius">
                <p class="mockup__category-name categoryBlockTextColor">Music</p>
            </div>
            <div class="mockup__category-item categoryBlockBackgroundColor__background categoryBorderRadius">
                <p class="mockup__category-name categoryBlockTextColor">Movies & TV</p>
            </div>
            <div class="mockup__category-item categoryBlockBackgroundColor__background categoryBorderRadius">
                <p class="mockup__category-name categoryBlockTextColor">Video Games</p>
            </div>
            <div class="mockup__category-item categoryBlockBackgroundColor__background categoryBorderRadius">
                <p class="mockup__category-name categoryBlockTextColor">Office Supplies</p>
            </div>
        </div>
    </div>

    <h4 class="mockup__second-heading categoriesHeaderColor">Hot deals</h4>
    <div class="mockup__carousel-container">
        <div class="mockup__carousel-product productBorderColor__border">
            <p class="mockup__carousel-product-badge productDiscountColor__background borderRadius">Discount 17%</p>
            <img src="{$images_dir}/addons/mobile_app/nokia.jpg" class="mockup__carousel-product-preview"/>
            <p class="mockup__carousel-product-describe">
                <span class="mockup__carousel-product-name">Apple iPad with Retina</span>
                <span class="mockup__carousel-product-cost">$499.00</span>
            </p>
        </div>
    </div>

    <h4 class="mockup__second-heading categoriesHeaderColor">Sale</h4>
    <div class="mockup__carousel-container">
        <div class="mockup__carousel-container--swiper">
            <div class="mockup__carousel-product mockup__carousel-product--swiper productBorderColor__border">
                <img src="{$images_dir}/addons/mobile_app/product_preview.gif" class="mockup__carousel-product-preview"/>
                <p class="mockup__carousel-product-describe">
                    <span class="mockup__carousel-product-name mockup__carousel-product-name--swiper">Mac OS X Lion: The Missing Manual</span>
                    <span class="mockup__carousel-product-review">
                        <i class="fa fa-star fa-lg ratingStarsColor"></i>
                        <i class="fa fa-star fa-lg ratingStarsColor"></i>
                        <i class="fa fa-star fa-lg ratingStarsColor"></i>
                        <i class="fa fa-star fa-lg ratingStarsColor"></i>
                        <i class="fa fa-star-half fa-lg ratingStarsColor"></i>
                    </span>
                    <span class="mockup__carousel-product-cost">$499.00</span>
                </p>
            </div>
            <div class="mockup__carousel-product mockup__carousel-product--swiper productBorderColor__border">
                <img src="{$images_dir}/addons/mobile_app/nokia.jpg" class="mockup__carousel-product-preview"/>
                <p class="mockup__carousel-product-describe">
                    <span class="mockup__carousel-product-name mockup__carousel-product-name--swiper">Apple iPad with Retina</span>
                    <span class="mockup__carousel-product-cost">$499.00</span>
                </p>
            </div>
            <div class="mockup__carousel-product mockup__carousel-product--swiper productBorderColor__border">
                <img src="{$images_dir}/addons/mobile_app/led.jpg" class="mockup__carousel-product-preview"/>
                <p class="mockup__carousel-product-describe">
                    <span class="mockup__carousel-product-name mockup__carousel-product-name--swiper">LED 8800 Series Smart TV</span>
                    <span class="mockup__carousel-product-cost">$499.00</span>
                </p>
            </div>
        </div>

        <div class="mockup__carousel-dots--swiper">
            <i class="fa fa-circle dotsSwiperColor"></i>
            <i class="fa fa-circle"></i>
        </div>
    </div>
{/capture}

{include 
    file="addons/mobile_app/components/container.tpl" 
    content=$smarty.capture.main 
    title=__("mobile_app.section.main") 
    meta="mockup__category"
    input_name="other"
}