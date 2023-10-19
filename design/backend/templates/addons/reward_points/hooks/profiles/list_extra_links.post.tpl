{if $user.user_type == "C" && fn_check_permissions("reward_points", "userlog", "admin", "GET")}
    <li><a href="{"reward_points.userlog?user_id=`$user.user_id`"|fn_url}">{__("points")} ({if $user.points}{$user.points|@unserialize}{else}0{/if})</a></li>
{/if}