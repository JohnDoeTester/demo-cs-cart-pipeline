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


namespace Tygh\Addons\MasterProducts\Product;


use Tygh\Addons\ProductVariations\Tools\QueryFactory;

/**
 * Class Repository
 *
 * @package Tygh\Addons\MasterProducts\Product
 */
class Repository
{
    /** @var string  */
    const TABLE_PRODUCTS = 'products';

    /** @var string  */
    const TABLE_PRODUCT_DESCRIPTIONS = 'product_descriptions';

    /** @var string  */
    const TABLE_COMPANY = 'companies';

    /** @var \Tygh\Addons\ProductVariations\Tools\QueryFactory */
    protected $query_factory;

    /** @var array */
    protected $lang_codes = [];

    /**
     * Repository constructor.
     *
     * @param \Tygh\Addons\ProductVariations\Tools\QueryFactory $query_factory
     * @param array                                             $lang_codes
     */
    public function __construct(QueryFactory $query_factory, array $lang_codes = [])
    {
        $this->query_factory = $query_factory;
        $this->lang_codes = $lang_codes;
    }

    /**
     * Finds product by product identifier
     *
     * @param int   $product_id
     * @param array $extends
     *
     * @return array
     */
    public function findProduct($product_id, array $extends = ['product_name'])
    {
        $result = $this->findProducts([$product_id], $extends);

        return reset($result);
    }

    /**
     * Finds products by product identifiers
     *
     * @param array $product_ids
     * @param array $extends
     *
     * @return array Indexed by product_id
     */
    public function findProducts(array $product_ids, array $extends = ['product_name'])
    {
        list($products) = fn_get_products([
            'extend'                   => $extends,
            'pid'                      => $product_ids,
            'group_child_variations'   => false,
            'remove_company_condition' => true,
            'sort_by'                  => 'null'
        ]);

        $products = fn_sort_by_ids($products, array_combine($product_ids, $product_ids));

        return $products;
    }

    /**
     * Finds master product ID of the specified vendor product ID.
     *
     * @param int $vendor_product_id Vendor product ID
     *
     * @return int Master product ID or 0 if none found
     */
    public function findMasterProductId($vendor_product_id)
    {
        $query = $this->createQuery(
            self::TABLE_PRODUCTS,
            ['product_id' => $vendor_product_id],
            ['master_product_id']
        );

        return (int) $query->scalar();
    }

    /**
     * Finds vendor products info of the specified vendor product IDs.
     *
     * @param int $vendor_product_id Vendor product ID
     *
     * @return int[] [product_id => [master_product_id, company_id]]
     */
    public function findVendorProductsInfo(array $vendor_product_ids)
    {
        $query = $this->createQuery(
            self::TABLE_PRODUCTS,
            ['product_id' => $vendor_product_ids],
            ['product_id', 'master_product_id', 'company_id']
        );

        return $query->select('product_id');
    }

    /**
     * Finds vendor product ID of the specified master product ID.
     *
     * @param int $master_product_id Master product ID
     * @param int $company_id        Vendor company ID
     *
     * @return int Vendor product ID or 0 if none found
     */
    public function findVendorProductId($master_product_id, $company_id)
    {
        $query = $this->createQuery(
            self::TABLE_PRODUCTS,
            ['master_product_id' => $master_product_id, 'company_id' => $company_id],
            ['product_id']
        );

        return (int) $query->scalar();
    }

    /**
     * Finds list of vendor products IDs.
     *
     * @param int           $master_product_id                     Master product ID
     * @param string[]|null $status                                Status of vendor products. Set to null to disable filtering
     * @param string[]|null $company_status                        Status of vendor. Set to null to disable filtering
     * @param bool          $is_show_out_of_stock_products_enabled Status of the show_out_of_stock_products setting
     * @param int           $company_id                            Company ID
     *
     * @return int[] Vendor product IDs
     */
    public function findVendorProductIds($master_product_id, array $status = null, array $company_status = null, $is_show_out_of_stock_products_enabled = true, $company_id = 0)
    {
        if (!$master_product_id) {
            return [];
        }

        $query = $this->createQuery(
            [self::TABLE_PRODUCTS => 'products'],
            ['master_product_id' => (int) $master_product_id],
            ['products.product_id']
        );

        if ($status !== null) {
            $query->addInCondition('status', $status);
        }

        if (!$is_show_out_of_stock_products_enabled) {
            $query->addCondition('products.amount > 0');
        }

        if (!empty($company_id)) {
            $query->addCondition('products.company_id = ?i', [$company_id]);
        }

        if ($company_status !== null) {
            $query->addInnerJoin('company', self::TABLE_COMPANY, ['company_id' => 'company_id']);
            $query->addConditions(['status' => $company_status], 'company');
        }

        $ids = $query->column();

        if ($ids) {
            return array_map('intval', $ids);
        }

        return [];
    }

    /**
     * Gets count of vendor products.
     *
     * @param int           $master_product_id                     Master product ID
     * @param string[]|null $status                                Status of vendor products. Set to null to disable filtering
     * @param string[]|null $company_status                        Status of vendor. Set to null to disable filtering
     * @param bool          $is_show_out_of_stock_products_enabled Status of the show_out_of_stock_products setting
     *
     * @return int
     */
    public function getVendorProductsCount($master_product_id, array $status = null, array $company_status = null, $is_show_out_of_stock_products_enabled = false)
    {
        if (!$master_product_id) {
            return 0;
        }

        $query = $this->createQuery(
            [self::TABLE_PRODUCTS => 'products'],
            ['master_product_id' => (int) $master_product_id],
            ['COUNT(products.product_id)']
        );

        if ($status !== null) {
            $query->addInCondition('status', $status);
        }

        if (!$is_show_out_of_stock_products_enabled) {
            $query->addCondition('products.amount > 0');
        }

        if ($company_status !== null) {
            $query->addInnerJoin('company', self::TABLE_COMPANY, ['company_id' => 'company_id']);
            $query->addConditions(['status' => $company_status], 'company');
        }

        return (int) $query->scalar();
    }

    /**
     * Gets sum quantity of vendor products.
     *
     * @param int           $master_product_id Master product ID
     * @param string[]|null $status            Status of vendor products. Set to null to disable filtering
     * @param string[]|null $company_status    Status of vendor. Set to null to disable filtering
     * @param int[]|null    $company_id        Vendor's company id. Set to null to disable filtering
     *
     * @return int
     */
    public function getVendorProductsSumQuantity($master_product_id, array $status = null, array $company_status = null, array $company_id = null)
    {
        if (!$master_product_id) {
            return 0;
        }

        $query = $this->createQuery(
            [self::TABLE_PRODUCTS => 'products'],
            ['master_product_id' => (int) $master_product_id],
            ['SUM(products.amount)']
        );

        if ($status !== null) {
            $query->addInCondition('status', $status);
        }

        if ($company_status !== null) {
            $query->addInnerJoin('company', self::TABLE_COMPANY, ['company_id' => 'company_id']);
            $query->addConditions(['status' => $company_status], 'company');
        }

        if ($company_id !== null) {
            $query->addInnerJoin('company', self::TABLE_COMPANY, ['company_id' => 'company_id']);
            $query->addConditions(['company_id' => $company_id], 'company');
        }

        return (int) $query->scalar();
    }

    /**
     * Finds list of vendor products IDs by master product IDs and company ID
     *
     * @param array         $master_product_ids Master product IDs
     * @param int           $company_id         Vendor company ID
     * @param string[]|null $status             Status of vendor products. Set to null to disable filtering
     *
     * @return array<int, int>|array<int, array<int, int>> [master_product_id => vendor_product_id] | [master_product_id => [vendor_product_id => vendor_product_id]]
     */
    public function findVendorProductIdsByMasterProductIds(array $master_product_ids, $company_id = null, array $status = null)
    {
        if (!$master_product_ids) {
            return [];
        }

        $query = $this->createQuery(
            self::TABLE_PRODUCTS,
            ['master_product_id' => $master_product_ids],
            ['product_id', 'master_product_id']
        );

        if ($company_id) {
            $query->addConditions(['company_id' => $company_id]);
        }

        if ($status !== null) {
            $query->addInCondition('status', $status);
        }

        if ($company_id) {
            return (array) $query->column(['master_product_id', 'product_id']);
        } else {
            return (array) $query->select(['master_product_id', 'product_id', 'product_id']);
        }
    }

    /**
     * Creates product
     *
     * @param array $product_data
     *
     * @return int
     */
    public function createProduct(array $product_data)
    {
        unset($product_data['product_id']);

        $product_id = $this->createQuery(self::TABLE_PRODUCTS)
            ->insert($product_data);

        $this->updateProductPrice($product_id, $product_data['price']);

        $descriptions = [];

        foreach ($this->lang_codes as $lang_code) {
            $descriptions[] = [
                'product_id'        => $product_id,
                'lang_code'         => $lang_code,
                'product'           => $product_data['product'],
                'shortname'         => isset($product_data['shortname']) ? $product_data['shortname'] : '',
                'short_description' => isset($product_data['short_description']) ? $product_data['short_description'] : '',
                'full_description'  => isset($product_data['full_description']) ? $product_data['full_description'] : '',
                'meta_keywords'     => isset($product_data['meta_keywords']) ? $product_data['meta_keywords'] : '',
                'meta_description'  => isset($product_data['meta_description']) ? $product_data['meta_description'] : '',
                'search_words'      => isset($product_data['search_words']) ? $product_data['search_words'] : '',
                'page_title'        => isset($product_data['page_title']) ? $product_data['page_title'] : '',
                'promo_text'        => isset($product_data['promo_text']) ? $product_data['promo_text'] : '',
            ];
        }

        $this->createQuery(self::TABLE_PRODUCT_DESCRIPTIONS)->multipleInsert($descriptions);

        return $product_id;
    }

    /**
     * Updates product price
     *
     * @param int   $product_id
     * @param float $price
     * @param array $prices
     */
    public function updateProductPrice($product_id, $price, array $prices = [])
    {
        fn_update_product_prices($product_id, [
            'price'  => $price,
            'prices' => $prices,
        ]);
    }

    /**
     * Updates product data
     *
     * @param int   $product_id
     * @param array $data
     */
    public function updateProduct($product_id, array $data)
    {
        $this->createQuery(self::TABLE_PRODUCTS)
            ->addConditions(['product_id' => $product_id])
            ->update($data);
    }

    /**
     * Creates query instance
     *
     * @param string|array $table_id
     * @param array        $conditions
     * @param array        $fields
     * @param string       $table_alias
     *
     * @return \Tygh\Addons\ProductVariations\Tools\Query
     */
    protected function createQuery($table_id, array $conditions = [], array $fields = [], $table_alias = null)
    {
        return $this->query_factory->createQuery($table_id, $conditions, $fields, $table_alias);
    }

    /**
     * Gets product out of stock action
     *
     * @param string $product_id Product ID
     *
     * @return string|null Out of stock action
     */
    public function getProductOutOfStockActions($product_id)
    {
        if (!$product_id) {
            return null;
        }

        $query = $this->createQuery(
            [self::TABLE_PRODUCTS => 'products'],
            ['product_id' => (int) $product_id],
            ['out_of_stock_actions']
        );

        return $query->scalar();
    }
}