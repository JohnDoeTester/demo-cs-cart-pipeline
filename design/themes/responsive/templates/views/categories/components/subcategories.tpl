{if $subcategories}
    {math equation="ceil(n/c)" assign="rows" n=$subcategories|count c=$columns|default:"2"}
    {split data=$subcategories size=$rows assign="splitted_subcategories"}
    <ul class="subcategories clearfix">
        {hook name="categories:view_subcategories"}
        {foreach from=$splitted_subcategories item="ssubcateg"}
            {foreach from=$ssubcateg item=category name="ssubcateg"}
                {if $category}
                    <li class="ty-subcategories__item">
                        <a href="{"categories.view?category_id=`$category.category_id`"|fn_url}">
                            {if $category.main_pair}
                                {include file="common/image.tpl"
                                    show_detailed_link=false
                                    images=$category.main_pair
                                    no_ids=true
                                    image_id="category_image"
                                    image_width=$settings.Thumbnails.category_lists_thumbnail_width
                                    image_height=$settings.Thumbnails.category_lists_thumbnail_height
                                    class="ty-subcategories-img"
                                }
                            {/if}
                            <span {live_edit name="category:category:{$category.category_id}"}>{$category.category}</span>
                        </a>
                    </li>
                {/if}
            {/foreach}
        {/foreach}
        {/hook}
    </ul>
{/if}