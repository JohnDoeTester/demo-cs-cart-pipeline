export const params = {
    bottomPanelSelector: '#bp_bottom_panel',
    offBottomPanelSelector: '#bp_off_bottom_panel',

    bottomButtonsContainerSelector: '#bp_bottom_buttons',
    bottomButtonsSelector: '[data-bp-bottom-buttons]',
    bottomButtonsActiveClass: 'bp-bottom-buttons--active',
    bottomButtonDisabledClass: 'bp-bottom-button--disabled',
    onBottomPanelSelector: '#bp_on_bottom_panel',

    navItemSpecificSelector: '[data-bp-nav-item="{placeholder}"]',
    navItemSelector: '[data-bp-nav-item]',
    navItemActiveClass: 'bp-nav__item--active',
    navActiveSelector: '#bp-nav__active',
    navActiveActivatedClass: 'bp-nav__active--activated',

    modesItemSpecificSelector: '[data-bp-modes-item="{placeholder}"]',
    modesItemSelector: '[data-bp-modes-item]',
    modesItemNotDisabledSelector: '[data-bp-modes-item]:not(.bp-modes__item--disabled)',
    modesItemActiveClass: 'bp-modes__item--active',
    modesActiveSelector: '#bp-modes__active',
    modesActiveClass: 'bp-modes__active--{placeholder}',
    modesActiveClasses: [
        'bp-modes__active--preview',
        'bp-modes__active--build',
        'bp-modes__active--text',
        'bp-modes__active--theme',
    ],

    dropdownSelector: '[data-bp-toggle="dropdown"]',
    dropdownMenuClass: 'bp-dropdown-menu',
    dropdownMenuOpenClass: 'bp-dropdown-menu--open',
    dropdownMenuItemClass: 'bp-dropdown-menu__item',

    htmlSelector: 'html',
    htmlActiveClass: 'bp-panel-active',
};