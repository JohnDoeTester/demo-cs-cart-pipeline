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

namespace Tygh\Addons\ProductReviews\ProductReview;

use Tygh\Enum\ImagePairTypes;
use Tygh\Enum\YesNo;

class ImagesService
{
    const OBJECT_TYPE = 'product_reviews';

    /** @var int */
    protected $max_images_upload;

    /** @var array<string> */
    protected $allowed_extensions;

    /**
     * ImagesService constructor.
     *
     * @param array<string, string> $allowed_extensions List of allowed image extensions
     * @param int                   $max_images_upload  Maximum number of uploaded images
     *
     * @return void
     */
    public function __construct(array $allowed_extensions, $max_images_upload = 10)
    {
        $this->allowed_extensions = $allowed_extensions;
        $this->max_images_upload = $max_images_upload;
    }

    /**
     * @param int|array<int> $product_review_ids Product Review identifiers
     *
     * @return array<array<string|int|array<string|int>>>|array<array<array<string|int|array<string|int>>>>
     */
    public function getImagePairs($product_review_ids)
    {
        if (!$product_review_ids) {
            return [];
        }

        return fn_get_image_pairs($product_review_ids, self::OBJECT_TYPE, ImagePairTypes::ADDITIONAL, true, true);
    }

    /**
     * @param int $product_review_id Product review identifier
     *
     * @return void
     */
    public function deleteImagePairsByProductReviewId($product_review_id)
    {
        fn_delete_image_pairs($product_review_id, self::OBJECT_TYPE);
    }

    /**
     * @param int $product_review_id Product review identifier
     *
     * @return array<int>
     */
    public function attachImages($product_review_id)
    {
        $filtered = fn_filter_uploaded_data('product_review_data', $this->allowed_extensions);
        $filtered = array_slice($filtered, 0, $this->max_images_upload);

        $pairs_data = [];
        $position = 1;
        foreach (array_keys($filtered) as $key) {
            $pairs_data[$key] = [
                'type'      => ImagePairTypes::ADDITIONAL,
                'object_id' => 0,
                'position'  => $position++,
                'is_new'    => YesNo::YES,
            ];
        }

        return fn_update_image_pairs([], $filtered, $pairs_data, $product_review_id, self::OBJECT_TYPE);
    }

    /**
     * @param int|int[] $pair_ids Image pairs identifiers
     *
     * @return void
     */
    public function deleteImagePairs($pair_ids)
    {
        foreach ((array) $pair_ids as $pair_id) {
            fn_delete_image_pair($pair_id, self::OBJECT_TYPE);
        }
    }
}
