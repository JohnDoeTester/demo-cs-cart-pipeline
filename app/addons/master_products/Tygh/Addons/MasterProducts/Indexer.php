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


namespace Tygh\Addons\MasterProducts;


use Tygh\Database\Connection;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\YesNo;

/**
 * Class Indexer
 *
 * @package Tygh\Addons\MasterProducts
 *
 * phpcs:disable SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
 */
class Indexer
{
    /**
     * @var \Tygh\Database\Connection
     */
    private $db;

    /**
     * @var int[]
     */
    private $marked_product_ids = [];

    /**
     * @var int[]
     */
    private $marked_storefront_ids = [];

    /**
     * @var bool
     */
    private $is_deferred_function_registered = false;

    /**
     * @var callable
     */
    private $setting_provider;

    /**
     * Indexer constructor.
     *
     * @param \Tygh\Database\Connection $db               Database connection
     * @param callable                  $setting_provider Setting provider
     */
    public function __construct(Connection $db, callable $setting_provider)
    {
        $this->db = $db;
        $this->setting_provider = $setting_provider;
    }

    /**
     * Removes rows from master_products_storefront_offers_count index by product IDs
     *
     * @param int[] $product_ids Product IDs
     */
    public function clearStorefrontOffersCountIndexByProductIds(array $product_ids)
    {
        $this->db->query('DELETE FROM ?:master_products_storefront_offers_count WHERE product_id IN (?n)', $product_ids);
    }

    /**
     * Removes rows from master_products_storefront_offers_count index by product ID
     *
     * @param int $product_id Product ID
     */
    public function clearStorefrontOffersCountIndexByProductId($product_id)
    {
        $this->clearStorefrontOffersCountIndexByProductIds([$product_id]);
    }

    /**
     * Removes rows from master_products_storefront_offers_count index by storefront IDs
     *
     * @param int[] $storefront_ids Storefront IDs
     */
    public function clearStorefrontOffersCountIndexByStorefrontIds(array $storefront_ids)
    {
        $this->db->query('DELETE FROM ?:master_products_storefront_offers_count WHERE storefront_id IN (?n)', $storefront_ids);
    }

    /**
     * Removes rows from master_products_storefront_offers_count index by storefront ID
     *
     * @param int $storefront_id Storefront ID
     */
    public function clearStorefrontOffersCountIndexByStorefrontId($storefront_id)
    {
        $this->clearStorefrontOffersCountIndexByStorefrontIds([$storefront_id]);
    }

    /**
     * Reindexes master_products_storefront_offers_count index by master product IDs
     *
     * @param int[] $product_ids Product IDs
     */
    public function reindexStorefrontOffersCountByProductIds(array $product_ids)
    {
        $this->clearStorefrontOffersCountIndexByProductIds($product_ids);
        $this->reindexStorefrontOffersCount(['product_ids' => $product_ids]);
    }

    /**
     * Reindexes master_products_storefront_offers_count index by storefront IDs
     *
     * @param int[] $storefront_ids Storefront IDs
     */
    public function reindexStorefrontOffersCountByStorefrontIds(array $storefront_ids)
    {
        $this->clearStorefrontOffersCountIndexByStorefrontIds($storefront_ids);
        $this->reindexStorefrontOffersCount(['storefront_ids' => $storefront_ids]);
    }

    /**
     * Marks master product to reindex master_products_storefront_offers_count index
     *
     * @param int $product_id Master product ID
     */
    public function markMasterProductToReindexStorefrontOffersCount($product_id)
    {
        $product_id = (int) $product_id;
        $this->marked_product_ids[$product_id] = $product_id;

        $this->registerDeferedFunction();
    }

    /**
     * Marks storefront to reindex master_products_storefront_offers_count index
     *
     * @param int $storefront_id Storefront ID
     */
    public function markStorefrontToReindexStorefrontOffersCount($storefront_id)
    {
        $storefront_id = (int) $storefront_id;
        $this->marked_storefront_ids[$storefront_id] = $storefront_id;

        $this->registerDeferedFunction();
    }

    /**
     * Marks all storefronts to reindex master_products_storefront_offers_count index
     */
    public function markAllStorefrontToReindexStorefrontOffersCount()
    {
        $storefront_ids = $this->db->getColumn('SELECT storefront_id FROM ?:storefronts');

        foreach ($storefront_ids as $storefront_id) {
            $this->markStorefrontToReindexStorefrontOffersCount($storefront_id);
        }
    }

    /**
     * Marks storefront to reindex master_products_storefront_offers_count index by vendor ID
     *
     * @param int $vendor_id Vendor ID
     */
    public function markStorefrontToReindexStorefrontOffersCountByVendorId($vendor_id)
    {
        foreach ($this->getStorefrontIdsByVendorId($vendor_id) as $storefront_id) {
            $this->markStorefrontToReindexStorefrontOffersCount($storefront_id);
        }
    }

    /**
     * Gets storefront IDs by vendor ID
     *
     * @param int $vendor_id Vendor IDs
     *
     * @return int[]
     */
    private function getStorefrontIdsByVendorId($vendor_id)
    {
        $storefront_ids = $this->db->getColumn(
            'SELECT storefront_id FROM ?:storefronts '
            . ' WHERE '
            .   ' storefront_id NOT IN (SELECT storefront_id FROM ?:storefronts_companies)'
            .   ' OR storefront_id IN (SELECT storefront_id FROM ?:storefronts_companies WHERE company_id = ?i)',
            $vendor_id
        );

        return array_map(static function ($storefront_id) {
            return (int) $storefront_id;
        }, $storefront_ids);
    }

    /**
     * Reindexes master_products_storefront_offers_count index by product IDs
     *
     * @param array $params Reindex paramters
     *
     * @psalm-param array{
     *   product_ids?: int[],
     *   storefront_ids?: int[]
     * } $params
     */
    private function reindexStorefrontOffersCount(array $params)
    {
        $conditions = [
            'products_status'      => $this->db->quote('products.status = ?s', ObjectStatuses::ACTIVE),
            'companies_status'     => $this->db->quote('companies.status = ?s', ObjectStatuses::ACTIVE),
            'is_master_product_id' => 'products.master_product_id > 0'
        ];

        if (!empty($params['product_ids'])) {
            $conditions['master_product_id'] = $this->db->quote('products.master_product_id IN (?n)', $params['product_ids']);
        }

        if (!empty($params['storefront_ids'])) {
            $conditions['storefront_id'] = $this->db->quote('storefronts.storefront_id IN (?n)', $params['storefront_ids']);
        }

        $all_storefront_ids = $this->db->getColumn('SELECT storefront_id FROM ?:storefronts');
        $all_vendors_storefront_ids = $this->db->getColumn(
            'SELECT storefront_id FROM ?:storefronts WHERE storefront_id NOT IN'
            . ' (SELECT storefront_id FROM ?:storefronts_companies GROUP BY storefront_id)'
        );

        $show_out_of_stock_products_storefront_ids = [];

        foreach ($all_storefront_ids as $storefront_id) {
            if (!YesNo::toBool(call_user_func($this->setting_provider, 'General.show_out_of_stock_products', $storefront_id))) {
                continue;
            }

            $show_out_of_stock_products_storefront_ids[] = $storefront_id;
        }

        $conditions['products_amount'] = $this->db->quote(
            '(CASE WHEN (storefronts.storefront_id IN (?n)) THEN 1 ELSE products.amount END) > 0',
            $show_out_of_stock_products_storefront_ids
        );

        /**
         * Executes before master_products_storefront_offers_count index updated.
         * Allows to modify conditions.
         *
         * @param array<string, int[]>  $params                     Indexation parameters
         * @param array<string, string> $conditions                 SQL query conditions
         * @param array<int>            $all_vendors_storefront_ids ID of storefronts
         */
        fn_set_hook('master_products_reindex_storefront_offers_count', $params, $conditions, $all_vendors_storefront_ids);

        $sql = $this->db->quote(
            'SELECT products.master_product_id, storefronts.storefront_id, COUNT(*) AS count'
            . ' FROM ?:products AS products'
            . ' INNER JOIN ?:companies AS companies ON companies.company_id = products.company_id'
            . ' LEFT JOIN ?:storefronts_companies AS storefronts_companies ON storefronts_companies.company_id = companies.company_id'
            . ' LEFT JOIN ?:storefronts AS storefronts ON storefronts.storefront_id = storefronts_companies.storefront_id OR storefronts.storefront_id IN (?n)'
            . ' WHERE ?p'
            . ' GROUP BY products.master_product_id, storefronts.storefront_id ORDER BY NULL',
            $all_vendors_storefront_ids,
            implode(' AND ', $conditions)
        );

        $this->db->replaceSelectionInto('master_products_storefront_offers_count', ['product_id', 'storefront_id', 'count'], $sql);
    }

    /**
     * Removes rows from master_products_storefront_min_price index by product IDs
     *
     * @param int[] $product_ids Product IDs
     *
     * @return void
     */
    public function clearStorefrontMinPriceIndexByProductIds(array $product_ids)
    {
        $this->db->query('DELETE FROM ?:master_products_storefront_min_price WHERE product_id IN (?n)', $product_ids);
    }

    /**
     * Removes rows from master_products_storefront_min_price index by product ID
     *
     * @param int $product_id Product ID
     *
     * @return void
     */
    public function clearStorefrontMinPriceIndexByProductId($product_id)
    {
        $this->clearStorefrontMinPriceIndexByProductIds([$product_id]);
    }

    /**
     * Removes rows from master_products_storefront_min_price index by storefront IDs
     *
     * @param int[] $storefront_ids Storefront IDs
     *
     * @return void
     */
    public function clearStorefrontMinPriceIndexByStorefrontIds(array $storefront_ids)
    {
        $this->db->query('DELETE FROM ?:master_products_storefront_min_price WHERE storefront_id IN (?n)', $storefront_ids);
    }

    /**
     * Removes rows from master_products_storefront_min_price index by storefront ID
     *
     * @param int $storefront_id Storefront ID
     *
     * @return void
     */
    public function clearStorefrontMinPriceIndexByStorefrontId($storefront_id)
    {
        $this->clearStorefrontMinPriceIndexByStorefrontIds([$storefront_id]);
    }

    /**
     * Reindexes master_products_storefront_min_price index by master product IDs
     *
     * @param int[] $product_ids Product IDs
     *
     * @return void
     */
    public function reindexStorefrontMinPriceByProductIds(array $product_ids)
    {
        $this->clearStorefrontMinPriceIndexByProductIds($product_ids);
        $this->reindexStorefrontMinPrice(['product_ids' => $product_ids]);
    }

    /**
     * Reindexes master_products_storefront_min_price index by storefront IDs
     *
     * @param int[] $storefront_ids Storefront IDs
     *
     * @return void
     */
    public function reindexStorefrontMinPriceByStorefrontIds(array $storefront_ids)
    {
        $this->clearStorefrontMinPriceIndexByStorefrontIds($storefront_ids);
        $this->reindexStorefrontMinPrice(['storefront_ids' => $storefront_ids]);
    }

    /**
     * Marks master product to reindex master_products_storefront_min_price index
     *
     * @param int $product_id Master product ID
     *
     * @return void
     */
    public function markMasterProductToReindexStorefrontMinPrice($product_id)
    {
        $product_id = (int) $product_id;
        $this->marked_product_ids[$product_id] = $product_id;

        $this->registerDeferedFunction();
    }

    /**
     * Marks storefront to reindex master_products_storefront_min_price index
     *
     * @param int $storefront_id Storefront ID
     *
     * @return void
     */
    public function markStorefrontToReindexStorefrontMinPrice($storefront_id)
    {
        $storefront_id = (int) $storefront_id;
        $this->marked_storefront_ids[$storefront_id] = $storefront_id;

        $this->registerDeferedFunction();
    }

    /**
     * Marks all storefronts to reindex master_products_storefront_min_price index
     *
     * @return void
     */
    public function markAllStorefrontToReindexStorefrontMinPrice()
    {
        $storefront_ids = $this->db->getColumn('SELECT storefront_id FROM ?:storefronts');

        foreach ($storefront_ids as $storefront_id) {
            $this->markStorefrontToReindexStorefrontMinPrice($storefront_id);
        }
    }

    /**
     * Marks storefront to reindex master_products_storefront_min_price index by vendor ID
     *
     * @param int $vendor_id Vendor ID
     *
     * @return void
     */
    public function markStorefrontToReindexStorefrontMinPriceByVendorId($vendor_id)
    {
        foreach ($this->getStorefrontIdsByVendorId($vendor_id) as $storefront_id) {
            $this->markStorefrontToReindexStorefrontMinPrice($storefront_id);
        }
    }

    /**
     * Reindexes master_products_storefront_min_price index by product IDs
     *
     * @param array $params Reindex parameters
     *
     * @psalm-param array{
     *   product_ids?: int[],
     *   storefront_ids?: int[]
     * } $params
     *
     * @return void
     */
    private function reindexStorefrontMinPrice(array $params)
    {
        $conditions = [
            'products_status'      => $this->db->quote('products.status = ?s', ObjectStatuses::ACTIVE),
            'companies_status'     => $this->db->quote('companies.status = ?s', ObjectStatuses::ACTIVE),
            'is_master_product_id' => 'products.master_product_id > 0'
        ];

        if (!empty($params['product_ids'])) {
            $conditions['master_product_id'] = $this->db->quote('products.master_product_id IN (?n)', $params['product_ids']);
        }

        if (!empty($params['storefront_ids'])) {
            $conditions['storefront_id'] = $this->db->quote('storefronts.storefront_id IN (?n)', $params['storefront_ids']);
        }

        $all_storefront_ids = $this->db->getColumn('SELECT storefront_id FROM ?:storefronts');
        $all_vendors_storefront_ids = $this->db->getColumn(
            'SELECT storefront_id FROM ?:storefronts WHERE storefront_id NOT IN'
            . ' (SELECT storefront_id FROM ?:storefronts_companies GROUP BY storefront_id)'
        );

        $show_out_of_stock_products_storefront_ids = [];

        foreach ($all_storefront_ids as $storefront_id) {
            if (!YesNo::toBool(call_user_func($this->setting_provider, 'General.show_out_of_stock_products', $storefront_id))) {
                continue;
            }

            $show_out_of_stock_products_storefront_ids[] = $storefront_id;
        }

        $conditions['products_amount'] = $this->db->quote(
            '(CASE WHEN (storefronts.storefront_id IN (?n)) THEN 1 ELSE products.amount END) > 0',
            $show_out_of_stock_products_storefront_ids
        );

        /**
         * Executes before master_products_storefront_min_price index updated.
         * Allows to modify conditions.
         *
         * @param array<string, int[]>  $params                     Indexation parameters
         * @param array<string, string> $conditions                 SQL query conditions
         * @param array<int>            $all_vendors_storefront_ids ID of storefronts
         */
        fn_set_hook('master_products_reindex_storefront_min_price', $params, $conditions, $all_vendors_storefront_ids);

        $sql = $this->db->quote(
            'SELECT products.master_product_id, storefronts.storefront_id, MIN(prices.price) AS price'
            . ' FROM ?:product_prices AS prices'
            . ' LEFT JOIN ?:products AS products ON products.product_id = prices.product_id'
            . ' INNER JOIN ?:companies AS companies ON companies.company_id = products.company_id'
            . ' LEFT JOIN ?:storefronts_companies AS storefronts_companies ON storefronts_companies.company_id = companies.company_id'
            . ' LEFT JOIN ?:storefronts AS storefronts ON storefronts.storefront_id = storefronts_companies.storefront_id OR storefronts.storefront_id IN (?n)'
            . ' WHERE ?p'
            . ' GROUP BY products.master_product_id, storefronts.storefront_id ORDER BY NULL',
            $all_vendors_storefront_ids,
            implode(' AND ', $conditions)
        );

        $this->db->replaceSelectionInto('master_products_storefront_min_price', ['product_id', 'storefront_id', 'price'], $sql);
    }

    /**
     * Registers defered function to reindex
     */
    private function registerDeferedFunction()
    {
        if ($this->is_deferred_function_registered) {
            return;
        }

        register_shutdown_function(function () {
            if ($this->marked_product_ids) {
                $this->reindexStorefrontOffersCountByProductIds($this->marked_product_ids);
                $this->reindexStorefrontMinPriceByProductIds($this->marked_product_ids);
            }

            if ($this->marked_storefront_ids) {
                $this->reindexStorefrontOffersCountByStorefrontIds($this->marked_storefront_ids);
                $this->reindexStorefrontMinPriceByStorefrontIds($this->marked_storefront_ids);
            }
        });
        $this->is_deferred_function_registered = true;
    }
}
