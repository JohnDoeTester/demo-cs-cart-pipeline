{capture name="profile"}
    <div class="mockup__profile">
        <div class="mockup__profile-tabs">
            <ul class="tabs__container grayColor__background">
                <li class="tabs__el">Settings</li>
            </ul>

            <div class="tabs__content tabs__content--settings">
                <div class="mockup__profile-item menuItemsBorderColor">
                    <span class="mockup__profile-item-text menuTextColor">Language</span>
                    <span class="mockup__profile-item-value menuIconsColor">
                        RU
                        {include_ext file="common/icon.tpl" class="icon icon-angle-right"}
                    </span>
                </div>

                <div class="mockup__profile-item menuItemsBorderColor">
                    <span class="mockup__profile-item-text menuTextColor">Currency</span>
                    <span class="mockup__profile-item-value menuIconsColor">
                        $
                        {include_ext file="common/icon.tpl" class="icon icon-angle-right"}
                    </span>
                </div>
            </div>
        </div>

        <div class="mockup__profile-tabs">
            <ul class="tabs__container grayColor__background">
                <li class="tabs__el">Buyer</li>
            </ul>
            <div class="tabs__content tabs__content--buyer">
                <div class="mockup__profile-item menuItemsBorderColor">
                    <span class="mockup__profile-item-text">
                        {include_ext file="common/icon.tpl" class="icon-user menuIconsColor"}
                        <span class="menuTextColor">Profile</span>
                    </span>
                    <span class="mockup__profile-item-value">
                        {include_ext file="common/icon.tpl" class="icon icon-angle-right menuIconsColor"}
                    </span>
                </div>

                <div class="mockup__profile-item menuItemsBorderColor">
                    <span class="mockup__profile-item-text">
                        {include_ext file="common/icon.tpl" class="icon-list menuIconsColor"}
                        <span class="menuTextColor">Orders</span>
                    </span>
                    <span class="mockup__profile-item-value">
                        {include_ext file="common/icon.tpl" class="icon icon-angle-right menuIconsColor"}
                    </span>
                </div>

                <div class="mockup__profile-item menuItemsBorderColor">
                    <span class="mockup__profile-item-text">
                        {include_ext file="common/icon.tpl" class="icon-signout menuIconsColor"}
                        <span class="menuTextColor">Logout</span>
                    </span>
                    <span class="mockup__profile-item-value">
                        {include_ext file="common/icon.tpl" class="icon icon-angle-right menuIconsColor"}
                    </span>
                </div>
            </div>
        </div>

        <div class="mockup__profile-tabs">
            <ul class="tabs__container grayColor__background">
                <li class="tabs__el">Pages</li>
            </ul>

            <div class="tabs__content tabs__content--pages">
                <div class="mockup__profile-item menuItemsBorderColor">
                    <span class="mockup__profile-item-text menuTextColor">Contacts</span>
                    <span class="mockup__profile-item-value">
                        {include_ext file="common/icon.tpl" class="icon icon-angle-right menuIconsColor"}
                    </span>
                </div>

                <div class="mockup__profile-item menuItemsBorderColor">
                    <span class="mockup__profile-item-text menuTextColor">Returns and Exchanges</span>
                    <span class="mockup__profile-item-value">
                        <i class="icon icon-angle-right menuIconsColor"></i>
                    </span>
                </div>

                <div class="mockup__profile-item menuItemsBorderColor">
                    <span class="mockup__profile-item-text menuTextColor">Payment and shipping</span>
                    <span class="mockup__profile-item-value">
                        <i class="icon icon-angle-right menuIconsColor"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
{/capture}

{include
    file="addons/mobile_app/components/container.tpl"
    content=$smarty.capture.profile
    title=__("mobile_app.section.profile")
    navbar_title="Profile"
    input_name="profile"
}
