{capture name="category"}          
    <h3 class="mockup__main-heading categoriesHeaderColor">Categories</h3>

    <div class="mockup__category-container">
        <div class="mockup__category-item categoryBlockBackgroundColor__background categoryBorderRadius">
            <img 
                src="{$images_dir}/addons/mobile_app/cars.png" 
                class="mockup__category-preview"
            />
            <p class="mockup__category-name categoryBlockTextColor">Car Electronics</p>
        </div>
        <div class="mockup__category-item categoryBlockBackgroundColor__background categoryBorderRadius">
            <img 
                src="{$images_dir}/addons/mobile_app/tv.png" 
                class="mockup__category-preview"
            />
            <p class="mockup__category-name categoryBlockTextColor">TV & Video</p>
        </div>
        <div class="mockup__category-item categoryBlockBackgroundColor__background categoryBorderRadius">
            <img 
                src="{$images_dir}/addons/mobile_app/cell.png" 
                class="mockup__category-preview"
            />
            <p class="mockup__category-name categoryBlockTextColor">Cell Phones</p>
        </div>
        <div class="mockup__category-item categoryBlockBackgroundColor__background categoryBorderRadius">
            <img 
                src="{$images_dir}/addons/mobile_app/mp3.png" 
                class="mockup__category-preview"
            />
            <p class="mockup__category-name categoryBlockTextColor">MP3 Players</p>
        </div>
        <div class="mockup__category-item categoryBlockBackgroundColor__background categoryBorderRadius">
            <img 
                src="{$images_dir}/addons/mobile_app/camera.png" 
                class="mockup__category-preview"
            />
            <p class="mockup__category-name categoryBlockTextColor">Cameras & Photo</p>
        </div>
        <div class="mockup__category-item categoryBlockBackgroundColor__background categoryBorderRadius">
            <p class="mockup__category-name categoryBlockTextColor">Game consoles</p>
        </div>
    </div>
{/capture}

{include 
    file="addons/mobile_app/components/container.tpl" 
    content=$smarty.capture.category 
    title=__("mobile_app.section.category") 
    meta="mockup__category" 
    background_color_class="categoriesBackgroundColor__background"
    input_name="categories"
}