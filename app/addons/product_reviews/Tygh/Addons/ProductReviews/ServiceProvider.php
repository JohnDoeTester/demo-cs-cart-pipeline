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

namespace Tygh\Addons\ProductReviews;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Addons\ProductReviews\ProductReview\ImagesService;
use Tygh\Addons\ProductReviews\ProductReview\Repository as ProductReviewRepository;
use Tygh\Registry;
use Tygh\Tools\ImageHelper;
use Tygh\Tygh;

/**
 * Class ServiceProvider is intended to register services and components of the "Product reviews" add-on to the
 * application container.
 *
 * @package Tygh\Addons\ProductReviews
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     *
     * @return void
     */
    public function register(Container $app)
    {
        $app['addons.product_reviews.service'] = static function ($app) {
            return new Service($app['db']);
        };

        $app['addons.product_reviews.images_service'] = static function () {
            return new ImagesService(ImageHelper::getSupportedFormats(), Registry::ifGet('config.tweaks.reviews.max_images_upload', 10));
        };

        $app['addons.product_reviews.product_review.repository'] = static function (Container $app) {
            return new ProductReviewRepository(
                new Service($app['db']),
                self::getImagesService(),
                Registry::ifGet('addons.product_reviews', [])
            );
        };
    }

    /**
     * @return \Tygh\Addons\ProductReviews\Service
     */
    public static function getService()
    {
        return Tygh::$app['addons.product_reviews.service'];
    }

    /**
     * @return \Tygh\Addons\ProductReviews\ProductReview\ImagesService
     */
    public static function getImagesService()
    {
        return Tygh::$app['addons.product_reviews.images_service'];
    }

    /**
     * @return \Tygh\Addons\ProductReviews\ProductReview\Repository
     */
    public static function getProductReviewRepository()
    {
        return Tygh::$app['addons.product_reviews.product_review.repository'];
    }
}
