<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

defined('BOOTSTRAP') or die('Access denied');

/** @var array $schema */
$schema = [
    'strings.xml'          => [
        'path'      => implode(DIRECTORY_SEPARATOR, ['android', 'app', 'src', 'main', 'res', 'values', 'strings.xml']),
        'variables' => [
            '[app_name]' => 'app_settings.build.appName'
        ],
    ],
    'info.plist'          => [
        'path'      => implode(DIRECTORY_SEPARATOR, ['ios', 'csnative', 'info.plist']),
        'variables' => [
            '[app_name]' => 'app_settings.build.appName'
        ],
    ],
    'theme.js'            => [
        'path'    =>   implode(DIRECTORY_SEPARATOR, ['src', 'config', 'theme.js']),
        'variables' => [
            '[statusBarColor]'                   => 'app_appearance.colors.navbar.statusBarColor.value',
            '[navBarBackgroundColor]'            => 'app_appearance.colors.navbar.navBarBackgroundColor.value',
            '[navBarButtonColor]'                => 'app_appearance.colors.navbar.navBarButtonColor.value',
            '[navBarTitleFontSize]'              => 'app_appearance.colors.navbar.navBarTitleFontSize.value',
            '[navBarTextColor]'                  => 'app_appearance.colors.navbar.navBarTextColor.value',
            '[screenBackgroundColor]'            => 'app_appearance.colors.navbar.screenBackgroundColor.value',
            '[bottomTabsBackgroundColor]'        => 'app_appearance.colors.bottom_tabs.bottomTabsBackgroundColor.value',
            '[bottomTabsTextColor]'              => 'app_appearance.colors.bottom_tabs.bottomTabsTextColor.value',
            '[bottomTabsSelectedTextColor]'      => 'app_appearance.colors.bottom_tabs.bottomTabsSelectedTextColor.value',
            '[bottomTabsIconColor]'              => 'app_appearance.colors.bottom_tabs.bottomTabsIconColor.value',
            '[bottomTabsSelectedIconColor]'      => 'app_appearance.colors.bottom_tabs.bottomTabsSelectedIconColor.value',
            '[bottomTabsPrimaryBadgeColor]'      => 'app_appearance.colors.bottom_tabs.bottomTabsPrimaryBadgeColor.value',
            '[buttonBackgroundColor]'            => 'app_appearance.colors.product_screen.buttonBackgroundColor.value',
            '[buttonWithBackgroundTextColor]'    => 'app_appearance.colors.product_screen.buttonWithBackgroundTextColor.value',
            '[buttonWithoutBackgroundTextColor]' => 'app_appearance.colors.product_screen.buttonWithoutBackgroundTextColor.value',
            '[successColor]'                     => 'app_appearance.colors.other.successColor.value',
            '[infoColor]'                        => 'app_appearance.colors.other.infoColor.value',
            '[dangerColor]'                      => 'app_appearance.colors.other.dangerColor.value',
            '[darkColor]'                        => 'app_appearance.colors.product_screen.darkColor.value',
            '[grayColor]'                        => 'app_appearance.colors.product_screen.grayColor.value',
            '[borderRadius]'                     => 'app_appearance.colors.other.borderRadius.value',
            '[productDiscountColor]'             => 'app_appearance.colors.other.productDiscountColor.value',
            '[productBorderColor]'               => 'app_appearance.colors.other.productBorderColor.value',
            '[menuItemsBorderColor]'             => 'app_appearance.colors.profile.menuItemsBorderColor.value',
            '[categoriesBackgroundColor]'        => 'app_appearance.colors.categories.categoriesBackgroundColor.value',
            '[categoriesHeaderColor]'            => 'app_appearance.colors.categories.categoriesHeaderColor.value',
            '[categoryBlockBackgroundColor]'     => 'app_appearance.colors.categories.categoryBlockBackgroundColor.value',
            '[categoryBlockTextColor]'           => 'app_appearance.colors.categories.categoryBlockTextColor.value',
            '[categoryBorderRadius]'             => 'app_appearance.colors.categories.categoryBorderRadius.value',
            '[categoryEmptyImage]'               => 'app_appearance.colors.product_screen.categoryEmptyImage.value',
            '[ratingStarsColor]'                 => 'app_appearance.colors.product_screen.ratingStarsColor.value',
            '[discussionMessageColor]'           => 'app_appearance.colors.product_screen.discussionMessageColor.value',
            '[logoUrl]'                          => 'app_settings.build.logoUrl',
            '[menuIconsColor]'                   => 'app_appearance.colors.profile.menuIconsColor.value',
            '[menuTextColor]'                    => 'app_appearance.colors.profile.menuTextColor.value',
            '[mediumGrayColor]'                  => 'app_appearance.colors.product_screen.mediumGrayColor.value',
            '[dotsSwiperColor]'                  => 'app_appearance.colors.other.dotsSwiperColor.value',
        ],
    ],
    'index.js'            => [
        'path'      => implode(DIRECTORY_SEPARATOR, ['src', 'config', 'index.js']),
        'variables' => [
            '[apiKey]'                     => 'app_settings.utility.apiKey',
            '[baseUrl]'                    => 'app_settings.utility.baseUrl',
            '[siteUrl]'                    => 'app_settings.utility.siteUrl',
            '[shopName]'                   => 'app_settings.utility.shopName',
            '[version]'                    => 'app_settings.utility.version',
            '[pushNotifications]'          => static function (array $settings) {
                return $settings['app_settings']['utility']['pushNotifications'] ? 'true' : 'false';
            },
            '[pushNotificationsColor]'     => 'app_appearance.colors.other.pushNotificationsColor.value',
            '[applePay]'                   => static function (array $settings) {
                return isset($settings['app_settings']['apple_pay']['applePay']) && $settings['app_settings']['apple_pay']['applePay']
                    ? 'true'
                    : 'false';
            },
            '[applePayMerchantIdentifier]' => 'app_settings.apple_pay.applePayMerchantIdentifier',
            '[applePayMerchantName]'       => 'app_settings.apple_pay.applePayMerchantName',
            '[applePaySupportedNetworks]'  => static function (array $settings) {
                return !empty($settings['app_settings']['apple_pay']['applePaySupportedNetworks'])
                    ? "['" . implode("','", $settings['app_settings']['apple_pay']['applePaySupportedNetworks']) . "']"
                    : '[]';
            },
            '[googlePay]'                  => static function (array $settings) {
                return isset($settings['app_settings']['google_pay']['googlePay']) && $settings['app_settings']['google_pay']['googlePay']
                    ? 'true'
                    : 'false';
            },
            '[googlePayApiKey]'            => 'app_settings.google_pay.googlePayApiKey',
            '[googlePaySupportedNetworks]' => static function (array $settings) {
                return !empty($settings['app_settings']['google_pay']['googlePaySupportedNetworks'])
                    ? "['" . implode("','", $settings['app_settings']['google_pay']['googlePaySupportedNetworks']) . "']"
                    : '[]';
            },
        ],
    ],
    'build_settings.json' => [
        'path'      => implode(DIRECTORY_SEPARATOR, ['build_settings.json']),
        'variables' => [
            '[json]' => static function (array $settings) {
                return json_encode($settings['app_settings']['build'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            },
        ],
        'content'   => '[json]',
    ],
];

$schema['styles.xml'] = [
    'path' => implode(DIRECTORY_SEPARATOR, ['android', 'app', 'src', 'main', 'res', 'values', 'styles.xml']),
];

$schema['styles.xml']['content'] = <<<'XML'
<resources>

    <!-- Base application theme. -->
    <style name="AppTheme" parent="Theme.AppCompat.Light.NoActionBar">
        <!-- Customize your theme here. -->
        <item name="android:textColor">#000000</item>
        <item name="android:forceDarkAllowed">false</item>
    </style>

</resources>
XML;

$schema['strings.xml']['content'] = <<<'XML'
<resources>
    <string name="app_name">[app_name]</string>
</resources>
XML;

$schema['info.plist']['content'] = <<<'INF'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
	<key>CFBundleDevelopmentRegion</key>
	<string>en</string>
	<key>CFBundleDisplayName</key>
	<string>[app_name]</string>
	<key>CFBundleExecutable</key>
	<string>$(EXECUTABLE_NAME)</string>
	<key>CFBundleIdentifier</key>
	<string>$(PRODUCT_BUNDLE_IDENTIFIER)</string>
	<key>CFBundleInfoDictionaryVersion</key>
	<string>6.0</string>
	<key>CFBundleName</key>
	<string>$(PRODUCT_NAME)</string>
	<key>CFBundlePackageType</key>
	<string>APPL</string>
	<key>CFBundleShortVersionString</key>
	<string>1.0</string>
	<key>CFBundleSignature</key>
	<string>????</string>
	<key>CFBundleVersion</key>
	<string>1</string>
	<key>LSRequiresIPhoneOS</key>
	<true/>
	<key>NSAppTransportSecurity</key>
	<dict>
		<key>NSExceptionDomains</key>
		<dict>
			<key>localhost</key>
			<dict>
				<key>NSExceptionAllowsInsecureHTTPLoads</key>
				<true/>
			</dict>
		</dict>
	</dict>
	<key>NSCameraUsageDescription</key>
	<string>Camera Use Access Warning</string>
	<key>NSLocationWhenInUseUsageDescription</key>
	<string></string>
	<key>NSPhotoLibraryUsageDescription</key>
	<string>Photo Library Access Warning</string>
	<key>UIAppFonts</key>
	<array>
		<string>AntDesign.ttf</string>
		<string>Entypo.ttf</string>
		<string>EvilIcons.ttf</string>
		<string>Feather.ttf</string>
		<string>FontAwesome.ttf</string>
		<string>FontAwesome5_Brands.ttf</string>
		<string>FontAwesome5_Regular.ttf</string>
		<string>FontAwesome5_Solid.ttf</string>
		<string>Foundation.ttf</string>
		<string>Ionicons.ttf</string>
		<string>MaterialIcons.ttf</string>
		<string>MaterialCommunityIcons.ttf</string>
		<string>SimpleLineIcons.ttf</string>
		<string>Octicons.ttf</string>
		<string>Zocial.ttf</string>
		<string>Fontisto.ttf</string>
	</array>
	<key>UIBackgroundModes</key>
	<array>
		<string>fetch</string>
		<string>remote-notification</string>
	</array>
	<key>UILaunchStoryboardName</key>
	<string>LaunchScreen</string>
	<key>UIRequiredDeviceCapabilities</key>
	<array>
		<string>armv7</string>
	</array>
	<key>UIRequiresFullScreen</key>
	<true/>
	<key>UIStatusBarHidden</key>
	<false/>
	<key>UIStatusBarStyle</key>
	<string>UIStatusBarStyleDarkContent</string>
	<key>UISupportedInterfaceOrientations</key>
	<array>
		<string>UIInterfaceOrientationPortrait</string>
	</array>
	<key>UISupportedInterfaceOrientations~ipad</key>
	<array>
		<string>UIInterfaceOrientationPortrait</string>
	</array>
	<key>UIUserInterfaceStyle</key>
	<string>Light</string>
	<key>UIViewControllerBasedStatusBarAppearance</key>
	<true/>
    <key>CFBundleAllowMixedLocalizations</key>
    <string>true</string>
</dict>
</plist>
INF;

$schema['theme.js']['content'] = <<<'THM'
export default {
  // Status bar color (android only).
  $statusBarColor: '[statusBarColor]',

  // The background of the top navigation bar.
  $navBarBackgroundColor: '[navBarBackgroundColor]',

  // The color of the top navigation bar buttons.
  $navBarButtonColor: '[navBarButtonColor]',

  // The size of the title text.
  $navBarTitleFontSize: [navBarTitleFontSize],

  // Button text color.
  $navBarTextColor: '[navBarTextColor]',

  // Main background.
  $screenBackgroundColor: '[screenBackgroundColor]',

  // Background, icons and text color of the bottom tab menu.
  $bottomTabsBackgroundColor: '[bottomTabsBackgroundColor]',
  $bottomTabsTextColor: '[bottomTabsTextColor]',
  $bottomTabsSelectedTextColor: '[bottomTabsSelectedTextColor]',
  $bottomTabsIconColor: '[bottomTabsIconColor]',
  $bottomTabsSelectedIconColor: '[bottomTabsSelectedIconColor]',

  // Color of the icon with the number of products.
  $bottomTabsPrimaryBadgeColor: '[bottomTabsPrimaryBadgeColor]',

  // The base color is used for action buttons.
  // For example add to cart.
  $buttonBackgroundColor: '[buttonBackgroundColor]',
  $buttonWithBackgroundTextColor: '[buttonWithBackgroundTextColor]',

  // The base color is used for action buttons without background.
  $buttonWithoutBackgroundTextColor: '[buttonWithoutBackgroundTextColor]',

  // Background color of messages.
  // Success, Info, Danger
  $successColor: '[successColor]',
  $infoColor: '[infoColor]',
  $dangerColor: '[dangerColor]',

  // Shades of gray. Used to display dividers and borders.
  $darkColor: '[darkColor]',
  $mediumGrayColor: '[mediumGrayColor]',
  $grayColor: '[grayColor]',

  // The radius of the rounding of buttons and form elements.
  $borderRadius: [borderRadius],

  // Discount label background on product.
  $productDiscountColor: '[productDiscountColor]',

  // Color of the border on the product list.
  // It is not visible by default.
  $productBorderColor: '[productBorderColor]',

  // Border color for menu items.
  $menuItemsBorderColor: '[menuItemsBorderColor]',

  // Icon color for menu items.
  $menuIconsColor: '[menuIconsColor]',

  // Text color for menu items.
  $menuTextColor: '[menuTextColor]',

  // Category background color.
  $categoriesBackgroundColor: '[categoriesBackgroundColor]',

  // Color of swiper dots.
  $dotsSwiperColor: '[dotsSwiperColor]',

  // The color of the title on the category screen.
  $categoriesHeaderColor: '[categoriesHeaderColor]',
  $categoryBlockBackgroundColor: '[categoryBlockBackgroundColor]',
  $categoryBlockTextColor: '[categoryBlockTextColor]',
  $categoryBorderRadius: [categoryBorderRadius],
  $categoryEmptyImage: '[categoryEmptyImage]',

  // Rating stars color.
  $ratingStarsColor: '[ratingStarsColor]',

  // Comment text color.
  $discussionMessageColor: '[discussionMessageColor]',

  // Store logo size 760x240.
  $logoUrl: '[logoUrl]',

  // Container padding.
  $containerPadding: 20,
};
THM;

$schema['index.js']['content'] = <<<'IND'
export default {
  // API KEY
  apiKey: '[apiKey]',
  // API URL
  baseUrl: '[baseUrl]',
  // SITE URL
  siteUrl: '[siteUrl]',
  // SHOP NAME
  shopName: '[shopName]',
  // VERSION MVE OR ULT
  version: '[version]',
  // Enable push notifications
  pushNotifications: [pushNotifications],
  pushNotificationsColor: '[pushNotificationsColor]',

  // Apple pay payments
  applePay: [applePay],
  applePayMerchantIdentifier: '[applePayMerchantIdentifier]',
  applePayMerchantName: '[applePayMerchantName]',
  applePaySupportedNetworks: [applePaySupportedNetworks],

  // Google pay payments
  googlePay: [googlePay],
  googlePayApiKey: '[googlePayApiKey]',
  googlePaySupportedNetworks: [googlePaySupportedNetworks],
};
IND;

return $schema;
