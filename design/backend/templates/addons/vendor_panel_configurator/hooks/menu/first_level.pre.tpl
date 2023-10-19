{$first_level_class = $first_level_class|default:""|replace:"nav__menu-item--root-hidden" : "" scope=parent}
{if $auth.user_type == "UserTypes::VENDOR"|enum && $smarty.const.BLOCK_MANAGER_MODE && $m.root_hidden}
    {$first_level_class = "`$first_level_class` nav__menu-item--root-hidden" scope=parent}
{/if}

{$first_level_class = $first_level_class|default:""|replace:"nav__menu-item--hidden-by-permissions" : "" scope=parent}
{if $auth.user_type == "UserTypes::VENDOR"|enum && $smarty.const.BLOCK_MANAGER_MODE && $m.hidden_by_permissions}
    {$first_level_class = "`$first_level_class` nav__menu-item--hidden-by-permissions" scope=parent}
{/if}