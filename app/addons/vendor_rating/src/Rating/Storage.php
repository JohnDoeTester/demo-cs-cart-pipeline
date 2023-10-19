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

namespace Tygh\Addons\VendorRating\Rating;

use Tygh\Database\Connection;

/**
 * Class Storage implements a rating storage mechanism.
 *
 * @package Tygh\Addons\VendorRating\Rating
 */
class Storage
{
    /**
     * @var \Tygh\Database\Connection
     */
    protected $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * @param int    $object_id
     * @param string $object_type
     *
     * @return int
     */
    public function getAbsoluteRating($object_id, $object_type)
    {
        $rating = (int) $this->db->getField(
            'SELECT rating FROM ?:absolute_rating WHERE ?w',
            [
                'object_id'   => $object_id,
                'object_type' => $object_type,
            ]
        );

        return $rating;
    }

    /**
     * @param int    $object_id
     * @param string $object_type
     *
     * @return int
     */
    public function getManualRating($object_id, $object_type)
    {
        $rating = (int) $this->db->getField(
            'SELECT rating FROM ?:manual_rating WHERE ?w',
            [
                'object_id'   => $object_id,
                'object_type' => $object_type,
            ]
        );

        return $rating;
    }

    /**
     * @param int    $object_id
     * @param string $object_type
     * @param int    $rating
     *
     * @return int
     */
    public function setAbsoluteRating($object_id, $object_type, $rating)
    {
        $this->db->replaceInto(
            'absolute_rating',
            [
                'object_id'         => $object_id,
                'object_type'       => $object_type,
                'rating'            => $rating,
                'updated_timestamp' => time(),
            ]
        );

        return $rating;
    }

    /**
     * @param int    $object_id
     * @param string $object_type
     * @param int    $rating
     *
     * @return int
     */
    public function setManualRating($object_id, $object_type, $rating)
    {
        $this->db->replaceInto(
            'manual_rating',
            [
                'object_id'         => $object_id,
                'object_type'       => $object_type,
                'rating'            => $rating,
                'updated_timestamp' => time(),
            ]
        );

        return $rating;
    }

    /**
     * @param string $object_type
     *
     * @return int
     */
    public function getMaxAbsoluteRating($object_type)
    {
        $max = (int) $this->db->getField(
            'SELECT MAX(rating) FROM ?:absolute_rating WHERE ?w',
            [
                'object_type' => $object_type,
            ]
        );

        return $max;
    }

    /**
     * @param int    $object_id
     * @param string $object_type
     */
    public function deleteManualRating($object_id, $object_type)
    {
        $this->db->query(
            'DELETE FROM ?:manual_rating WHERE ?w',
            [
                'object_type' => $object_type,
                'object_id'   => $object_id,
            ]
        );
    }

    /**
     * @param int    $object_id
     * @param string $object_type
     */
    public function deleteAbsoluteRating($object_id, $object_type)
    {
        $this->db->query(
            'DELETE FROM ?:absolute_rating WHERE ?w',
            [
                'object_type' => $object_type,
                'object_id'   => $object_id,
            ]
        );
    }

    /**
     * @param int    $object_id
     * @param string $object_type
     *
     * @return int
     */
    public function getAbsoluteRatingUpdatedAt($object_id, $object_type)
    {
        return (int) $this->db->getField(
            'SELECT updated_timestamp FROM ?:absolute_rating WHERE ?w',
            [
                'object_type' => $object_type,
                'object_id'   => $object_id,
            ]
        );
    }
}
