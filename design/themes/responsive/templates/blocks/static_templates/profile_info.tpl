{if $runtime.controller === 'profiles'}
    {if $runtime.mode === 'add'}
    <div class="ty-account-benefits">
        {__("text_profile_benefits") nofilter}
    </div>

    {elseif $runtime.mode == 'update'}
        <div class="ty-account-detail">
            <div>
                {__("text_profile_details") nofilter}
            </div>
            <div class="ty-account-detail__image">
                {include file="common/image.tpl" images = ["image_path" => "`$images_dir`/profile_details.png", "image_x" => 183, "image_y" => 206]}
            </div>
        </div>
    {/if}
{/if}