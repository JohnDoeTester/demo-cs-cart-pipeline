{section name="stars_rate" start="1" loop="6"}
    {$icon_star = ($smarty.section.stars_rate.index > $stars) ? "icon-star-empty" : "icon-star"}
    {include_ext file="common/icon.tpl" class=$icon_star}
{/section}
