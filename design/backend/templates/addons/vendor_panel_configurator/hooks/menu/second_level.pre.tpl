{$second_level_class = $second_level_class|default:""|replace:"nav__menu-subitem--hidden-by-permissions" : "" scope=parent}
{if $auth.user_type == "UserTypes::VENDOR"|enum && $smarty.const.BLOCK_MANAGER_MODE && $second_level.hidden_by_permissions}
    {$second_level_class = "`$second_level_class` nav__menu-subitem--hidden-by-permissions" scope=parent}
{/if}