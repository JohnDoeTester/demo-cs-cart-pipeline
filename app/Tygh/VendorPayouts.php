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

namespace Tygh;

use Tygh\Enum\VendorPayoutApprovalStatuses;
use Tygh\Enum\VendorPayoutTypes;
use Tygh\Enum\YesNo;

class VendorPayouts
{
    /**
     * @var array<VendorPayouts> $instance Self instances
     */
    protected static $instances;

    /**
     * @var \Tygh\Database\Connection $db Database connection
     */
    protected $db;

    /**
     * @var int $vendor Vendor identifier
     */
    protected $vendor;

    /**
     * @var array $currency Primary currency info
     */
    protected $currency;

    /**
     * VendorPayouts constructor.
     *
     * @param array $params Create parameters
     */
    public function __construct($params = array())
    {
        $this->db = isset($params['db']) ? $params['db'] : Tygh::$app['db'];
        $this->vendor = $params['vendor'];
    }

    /**
     * Gets instance of the VendorPayouts.
     *
     * @param array $params Instance parameters.
     *
     * @return self
     */
    public static function instance($params = array())
    {
        $params['vendor'] = isset($params['vendor']) ? $params['vendor'] : Registry::get('runtime.company_id');

        if (!isset(self::$instances[$params['vendor']])) {
            self::$instances[$params['vendor']] = new self($params);
        }

        return self::$instances[$params['vendor']];
    }

    /**
     * Updates existing payout or creates new
     *
     * @param array $data      Payout data
     *                         If payout_id is not empty in $data, the existing payout will be updated
     * @param int   $payout_id Payout identifier
     *                         When left empty, and no payout_id is in the $data, the new payout will be created
     *
     * @return int Updated/created payout identifier
     */
    public function update(array $data, $payout_id = 0)
    {
        /**
         * Executes at the very beginning of the method, allows to modify passed arguments.
         *
         * @param \Tygh\VendorPayouts $this      VendorPayouts instance
         * @param array               $data      Payout data
         * @param int                 $payout_id Payout identifier
         */
        fn_set_hook('vendor_payouts_update_pre', $this, $data, $payout_id);

        $payout_id = (int)$payout_id;

        if (!$payout_id && isset($data['payout_id'])) {
            $payout_id = $data['payout_id'];
            unset($data['payout_id']);
        }

        if (!$payout_id) {
            $action = 'create';
            // populate default fields only when creating payouts
            $data = $this->prepareData($data);
        } else {
            $action = 'update';
        }

        /**
         * Executes before creating/updating payout data is performed, allows to modify stored payout data.
         *
         * @param \Tygh\VendorPayouts $this      VendorPayouts instance
         * @param array               $data      Payout data
         * @param int                 $payout_id Payout identifier
         * @param string              $action    Performed action: 'create' or 'update'
         */
        fn_set_hook('vendor_payouts_update', $this, $data, $payout_id, $action);

        if ($action == 'create') {
            $payout_id = $this->db->query('INSERT INTO ?:vendor_payouts ?e', $data);
        } else {
            $this->db->query('UPDATE ?:vendor_payouts SET ?u WHERE payout_id = ?i', $data, $payout_id);
        }

        /**
         * Executes at the end of the method after the payout data is saved, allows to modify returned value.
         *
         * @param \Tygh\VendorPayouts $this      VendorPayouts instance
         * @param array               $data      Payout data
         * @param int                 $payout_id Created/saved payout identifier
         * @param string              $action    Performed action: 'create' or 'update'
         */
        fn_set_hook('vendor_payouts_update_post', $this, $data, $payout_id, $action);

        return $payout_id;
    }

    /**
     * Fills missing fields for payout data.
     *
     * @param array $data Payout data
     *
     * @return array Payout data with missing fields populated.
     */
    private function prepareData(array $data)
    {
        $default_data = array(
            'payout_date' => TIME,
            'start_date' => TIME,
            'end_date' => TIME,
            'payout_type' => VendorPayoutTypes::OTHER,
        );

        $data = array_merge($default_data, $data);

        if ($this->vendor) {
            $data['company_id'] = $this->vendor;
        }

        if (!empty($data['details']) && is_array($data['details'])) {
            $data['details'] = serialize($data['details']);
        }

        return $data;
    }

    /**
     * Deletes payouts by the identifier(s).
     *
     * @param array<int>|int $ids Payout identifier(s)
     *
     * @return bool True, if payouts were deleted
     */
    public function delete($ids)
    {
        if (!$ids) {
            return false;
        }

        if (is_array($ids)) {
            $this->db->query('DELETE FROM ?:vendor_payouts WHERE payout_id IN (?n)', $ids);
        } else {
            $this->db->query('DELETE FROM ?:vendor_payouts WHERE payout_id = ?i', $ids);
        }

        return true;
    }

    /**
     * Provides list of payouts with company names and payout descriptions.
     *
     * @param array $params         Request parameters
     * @param int   $items_per_page Amount of items per page
     *
     * @return array Payouts, search parameters
     */
    public function getList(array $params, $items_per_page = 0)
    {
        $default_params = array(
            'page' => 1,
            'items_per_page' => $items_per_page,
        );

        // Define sort fields
        $sortings = array(
            'sort_vendor' => 'companies.company',
            'sort_period' => 'payouts.start_date',
            'sort_amount' => 'payout_amount',
            'sort_date' => 'payouts.payout_date',
        );

        $join = $this->db->quote(
            ' LEFT JOIN ?:orders AS orders ON (payouts.order_id = orders.order_id)'
            . ' LEFT JOIN ?:companies AS companies ON (payouts.company_id = companies.company_id)'
        );

        $params = array_merge($default_params, $params);

        list($condition, $date_condition, $params) = $this->populateGetListConditions($params);

        $sorting = db_sort($params, $sortings, 'sort_date', 'desc');
        $limit = db_paginate($params['page'], $params['items_per_page']);

        $fields = array(
            'payouts.*' => 'payouts.*',
            'company' => 'companies.company',
            'original_payout_amount' => 'payouts.payout_amount',
            'payout_amount' => 'IF(payouts.order_id <> 0, payouts.order_amount, payouts.payout_amount)',
            'date' => "IF(payouts.order_id <> 0, payouts.end_date, '')",
        );

        /**
         * Executes before getting payouts list from the database,
         * allows to modify data passed to the query.
         *
         * @param VendorPayouts $instance       VendorPayouts instance
         * @param array         $params         Search parameters
         * @param int           $items_per_page Items per page
         * @param array         $fields         SQL query fields
         * @param string        $join           JOIN statement
         * @param string        $condition      General condition to filter payouts
         * @param string        $date_condition Additional condition to filter payouts by date
         * @param string        $sorting        ORDER BY statemet
         * @param string        $limit          LIMIT statement
         */
        fn_set_hook('vendor_payouts_get_list', $this, $params, $items_per_page, $fields, $join, $condition, $date_condition, $sorting, $limit);

        $payouts = $this->db->getArray(
            "SELECT SQL_CALC_FOUND_ROWS ?p"
            . " FROM ?:vendor_payouts AS payouts"
            . " ?p"
            . " WHERE ?p AND ?p"
            . " GROUP BY payouts.payout_id ?p ?p",
            $this->buildQueryFields($fields),
            $join,
            $condition,
            $date_condition,
            $sorting,
            $limit
        );

        $params['total_items'] = $this->db->getField("SELECT FOUND_ROWS()");

        foreach ($payouts as $i => $payout) {
            if ($payout['payout_type'] == VendorPayoutTypes::WITHDRAWAL) {
                $payout['payout_amount'] = $payout['original_payout_amount'];
            }

            $payouts[$i]['payout_type_description'] = $this->getDescription($payout);
            $payouts[$i]['display_amount'] = $payout['payout_amount'];
            // payouts have to be displayed inverted for vendors
            if ($this->getVendor() && $payout['payout_type'] == VendorPayoutTypes::PAYOUT) {
                $payouts[$i]['display_amount'] = -$payout['payout_amount'];
            } else {
                $payouts[$i]['display_amount'] = $payout['payout_amount'];
            }

            if (isset($payout['extra'])) {
                $payouts[$i]['extra'] = json_decode($payout['extra'], true);
            }
        }

        return array($payouts, $params);
    }

    /**
     * Calculates income and balance for the range of payouts.
     *
     * @param  array $params
     * @return array
     */
    public function getTotals(array $params)
    {
        $totals = array(
            'income' => 0,
            'income_carried_forward' => 0,
            'balance' => 0,
            'balance_carried_forward' => 0,
        );

        list(, , $params) = $this->populateGetListConditions($params);

        list($totals['income'], $totals['income_carried_forward']) = $this->getIncome($params);
        list($totals['balance'], $totals['balance_carried_forward']) = $this->getBalance($params);

        return $totals;
    }

    /**
     * Provides the description of the payout.
     *
     * @param array  $payout   Payout data
     * @param string $language Two-letter language code
     *
     * @return string Description of the payout
     */
    public static function getDescription(array $payout, $language = CART_LANGUAGE)
    {
        $fields = self::populatePayoutFields($payout);

        if ($payout['payout_type'] == VendorPayoutTypes::ORDER_PLACED
            || $payout['payout_type'] == VendorPayoutTypes::ORDER_CHANGED
            || $payout['payout_type'] == VendorPayoutTypes::ORDER_REFUNDED
        ) {
            $fields["[order_url]"] = fn_url("orders.details?order_id={$payout['order_id']}");
        }

        if (!in_array($payout['payout_type'], VendorPayoutTypes::getAll())) {
            $type = VendorPayoutTypes::OTHER;
        } else {
            $type = $payout['payout_type'];
        }

        /**
         * Executes before getting the payout description, allows to modify data passed to the translation function.
         *
         * @param array  $payout   Payout data
         * @param string $language Two-letter language code
         * @param array  $fields   Payout fields used to translate description
         * @param string $type     Payout type used to get language variable
         */
        fn_set_hook('vendor_payouts_get_description', $payout, $language, $fields, $type);

        $description = __("vendor_payouts.type.{$type}.description", $fields, $language);

        return $description;
    }

    /**
     * Populates translation substitutions for ::__() based on the payout data.
     *
     * @param array $payout Payout data
     *
     * @return array Translation substitutions
     */
    protected static function populatePayoutFields(array $payout)
    {
        $p_fields = array();
        foreach ($payout as $field => $value) {
            $p_fields["[{$field}]"] = $value;
        }

        return $p_fields;
    }

    /**
     * Builds fields statement part of the SQL query.
     *
     * @param array $fields Associative array of (alias => field)
     *
     * @return string Fields statement
     */
    protected function buildQueryFields(array $fields)
    {
        $condition = array();
        foreach ($fields as $alias => $field) {
            if ($alias == $field) {
                $condition[] = $field;
            } else {
                $condition[] = "{$field} AS {$alias}";
            }
        }

        return implode(', ', $condition);
    }

    /**
     * Populates conditions for \Tygh\VendorPayouts::getListWithTotals().
     *
     * @param array $params Request parameters
     *
     * @return array Condition, date condition and search parameters
     */
    protected function populateGetListConditions(array $params)
    {
        $default_params = [
            'simple' => false,
        ];

        $params = array_merge($default_params, $params);

        $condition = $date_condition = '1';

        if (!empty($params['time_from']) || !empty($params['time_to'])) {
            $dates = $this->getTransactionsPeriod();

            if (!empty($params['time_from'])) {
                $params['time_from'] = fn_parse_date($params['time_from']);
            } else {
                $params['time_from'] = $dates['time_from'];
            }

            if (!empty($params['time_to'])) {
                if (is_numeric($params['time_to'])) {
                    $params['time_to'] = fn_parse_date($params['time_to']);
                } else {
                    $params['time_to'] = fn_parse_date($params['time_to']) + SECONDS_IN_DAY - 1; //Get the day ending time;
                }
            } else {
                $params['time_to'] = $dates['time_to'];
            }

            if ($params['time_to'] < $params['time_from']) {
                $params['time_to'] = $params['time_from'];
            }

            $date_condition .= $this->db->quote(
                ' AND payouts.payout_date BETWEEN ?i AND ?i',
                $params['time_from'],
                $params['time_to']
            );
        }

        // Order statuses condition
        if (!$params['simple'] && $statuses = self::getPayoutOrderStatuses()) {
            $condition .= $this->db->quote(' AND (orders.status IN (?a) OR payouts.order_id = 0)', $statuses);
        }

        if (!empty($params['payout_id'])) {
            $condition .= $this->db->quote(' AND payouts.payout_id = ?i', $params['payout_id']);
        }

        if (!empty($params['order_id'])) {
            $condition .= $this->db->quote(' AND payouts.order_id IN (?n)', (array)$params['order_id']);
        }

        if (!empty($params['payout_type'])) {
            if (is_array($params['payout_type']) && in_array(VendorPayoutTypes::OTHER, $params['payout_type'])
                || $params['payout_type'] == VendorPayoutTypes::OTHER
            ) {
                $condition .= $this->db->quote(
                    ' AND (payout_type NOT IN (?a) OR payouts.payout_type IN (?a))',
                    VendorPayoutTypes::getAll(),
                    (array)$params['payout_type']
                );
            } else {
                $condition .= $this->db->quote(' AND payouts.payout_type IN (?a)', (array)$params['payout_type']);
            }

        }

        if (!empty($params['approval_status'])) {
            $condition .= $this->db->quote(' AND payouts.approval_status IN (?a)', (array)$params['approval_status']);
        }

        // Filter by vendor
        if ($this->vendor) {
            $params['vendor'] = $this->vendor;
        }

        if (!empty($params['vendor']) && $params['vendor'] != 'all') {
            $condition .= $this->db->quote(' AND payouts.company_id = ?i', $params['vendor']);
        }

        if (!empty($params['get_balance'])) {
            $condition .= $this->db->quote(' AND payouts.exclude_from_balance = ?s', YesNo::NO);
        }

        return array($condition, $date_condition, $params);
    }

    /**
     * Gets simple list of payouts.
     *
     * @param array $params Search parameters
     *
     * @return array Found payouts
     */
    public function getSimple(array $params)
    {
        $params['simple'] = true;

        list($condition, $date_condition,) = $this->populateGetListConditions($params);

        return $this->db->getArray('SELECT * FROM ?:vendor_payouts AS payouts WHERE ?p AND ?p', $condition, $date_condition);
    }

    /**
     * Gets order statuses that will be used for vendor payouts.
     *
     * @return array Statuses for payouts
     */
    public function getPayoutOrderStatuses()
    {
        $statuses = $this->db->getColumn(
            "SELECT ?:statuses.status"
            . " FROM ?:statuses"
            . " INNER JOIN ?:status_data"
                . " ON ?:status_data.status_id = ?:statuses.status_id"
            . " WHERE type = ?s"
                . " AND param = ?s"
                . " AND value = ?s",
            STATUSES_ORDER,
            'calculate_for_payouts',
            'Y'
        );

        /**
         * Getting order statuses that will be used for vendor payouts
         *
         * @param  array $statuses Statuses
         */
        fn_set_hook('get_order_payout_statuses', $statuses);

        return $statuses;
    }

    /**
     * Gets dates of the first and the last transaction as timestamps.
     *
     * @return array Dates
     */
    protected function getTransactionsPeriod()
    {
        $dates_query = $this->db->quote(
            'SELECT MIN(payout_date) AS time_from, MAX(payout_date) AS time_to'
            . ' FROM ?:vendor_payouts'
        );

        $dates = $this->getWithCache($dates_query, 'transactions_period');

        return $dates;
    }

    /**
     * Calculates income.
     *
     * @param array $params Search parameters
     *
     * @return array Income and income carried forward
     */
    public function getIncome($params = array())
    {
        /**
         * Executes before income calculation,
         * allows to modify data passed to the function.
         *
         * @param VendorPayouts $this   VendorPayouts instance
         * @param array         $params Search parameters
         */
        fn_set_hook('vendor_payouts_get_income_pre', $this, $params);

        unset($params['payout_type']);

        $fields = array(
            'orders_summary' => $this->db->quote(
                'SUM(payouts.order_amount)'
            )
        );

        // vendor: sum(O) - sum(P)
        // admin:  sum(C) + sum(P_approved)
        if ($this->vendor) {
            $fields['payouts_summary'] = $this->db->quote(
                'SUM(payouts.payout_amount * CASE WHEN payouts.payout_type = ?s THEN -1 ELSE 0 END * CASE WHEN payouts.payout_amount > 0 THEN 1 ELSE 0 END)',
                VendorPayoutTypes::PAYOUT
            );
        } else {
            $fields['payouts_summary'] = $this->db->quote(
                'SUM(payouts.payout_amount * CASE WHEN payouts.payout_type = ?s THEN 1 ELSE 0 END * CASE WHEN payouts.approval_status = ?s THEN 1 ELSE 0 END * CASE WHEN payouts.payout_amount > 0 THEN 1 ELSE 0 END)',
                VendorPayoutTypes::PAYOUT,
                VendorPayoutApprovalStatuses::COMPLETED
            );
        }

        $join = $this->db->quote('LEFT JOIN ?:orders AS orders ON orders.order_id = payouts.order_id');

        if ($this->vendor) {
            $params['approval_status'] = [
                VendorPayoutApprovalStatuses::COMPLETED,
                VendorPayoutApprovalStatuses::PENDING,
            ];
        }

        list($condition, $date_condition, $params) = $this->populateGetListConditions($params);

        /**
         * Executes before performing query to calculate vendor or admin income,
         * allows to modify data passed to the query.
         *
         * @param VendorPayouts $this           VendorPayouts instance
         * @param array         $params         Search parameters
         * @param array         $fields         SQL query fields
         * @param string        $join           JOIN statement
         * @param string        $condition      General condition to filter payouts
         * @param string        $date_condition Additional condition to filter payouts by date
         */
        fn_set_hook('vendor_payouts_get_income', $this, $params, $fields, $join, $condition, $date_condition);

        $income_query = $this->db->quote(
            "SELECT ?p"
            . " FROM ?:vendor_payouts AS payouts"
            . " ?p"
            . " WHERE ?p AND ?p",
            $this->buildQueryFields($fields),
            $join,
            $condition, $date_condition
        );

        $income = $this->getWithCache($income_query, 'income');

        $amount = $income['orders_summary'] + $income['payouts_summary'];
        $amount_carried_forward = null;

        $total_period = $this->getTransactionsPeriod();

        if ($total_period['time_from'] && isset($params['time_from']) && $total_period['time_from'] < $params['time_from']) {
            $income_carried_forward_query = $this->db->quote(
                "SELECT ?p"
                . " FROM ?:vendor_payouts AS payouts"
                . " ?p"
                . " WHERE ?p AND payouts.payout_date < ?i",
                $this->buildQueryFields($fields),
                $join,
                $condition, $params['time_from']
            );

            $income = $this->getWithCache($income_carried_forward_query, 'income_cf');

            $amount_carried_forward = $income['orders_summary'] + $income['payouts_summary'];
        }

        $amount = $this->roundRateValue($amount);
        $amount_carried_forward = $this->roundRateValue($amount_carried_forward);

        /**
         * Executes after the income is calculated,
         * allows to modify calculated values.
         *
         * @param VendorPayouts $this                   VendorPayouts instance
         * @param array         $params                 Search parameters
         * @param float         $amount                 Income
         * @param float|null    $amount_carried_forward Income carried forward
         */
        fn_set_hook('vendor_payouts_get_income_post', $this, $params, $amount, $amount_carried_forward);

        return array($amount, $amount_carried_forward);
    }

    /**
     * Calculates vendor balance.
     *
     * @param array $params Search parameters
     *
     * @return array Balance and balance carried forward
     */
    public function getBalance($params = array())
    {
        /**
         * Executes before balance calculation,
         * allows to modify data passed to the function.
         *
         * @param VendorPayouts $this   VendorPayouts instance
         * @param array         $params Search parameters
         */
        fn_set_hook('vendor_payouts_get_balance_pre', $this, $params);

        $default_params = [
            'approval_status' => [
                VendorPayoutApprovalStatuses::COMPLETED,
                VendorPayoutApprovalStatuses::PENDING
            ],
            'get_balance' => true
        ];
        $params = array_merge($default_params, $params);

        unset($params['payout_type']);

        $amount = 0;
        $amount_carried_forward = null;

        // administrators have no balance
        if ($this->vendor) {
            list($amount, $amount_carried_forward) = $this->getIncome($params);

            $fields = [
                'withdrawals_summary' => $this->db->quote(
                    'SUM(
                        payouts.payout_amount * 
                        CASE 
                            WHEN payouts.payout_type = ?s AND payouts.payout_amount > 0 
                            THEN 1 
                            WHEN payouts.payout_type = ?s AND payouts.payout_amount < 0 
                            THEN 1 
                            ELSE 0 
                        END
                        )',
                    VendorPayoutTypes::WITHDRAWAL,
                    VendorPayoutTypes::PAYOUT
                )
            ];

            $join = $this->db->quote('LEFT JOIN ?:orders AS orders ON orders.order_id = payouts.order_id');

            list($condition, $date_condition, $params) = $this->populateGetListConditions($params);

            /**
             * Executes before performing query to calculate vendor or admin withdrawals used to calculate balance,
             * allows to modify data passed to the query.
             *
             * @param VendorPayouts $this           VendorPayouts instance
             * @param array         $params         Search parameters
             * @param array         $fields         SQL query fields
             * @param string        $join           JOIN statement
             * @param string        $condition      General condition to filter payouts
             * @param string        $date_condition Additional condition to filter payouts by date
             */
            fn_set_hook('vendor_payouts_get_balance', $this, $params, $fields, $join, $condition, $date_condition);

            $withdrawals_query = $this->db->quote(
                "SELECT ?p"
                . " FROM ?:vendor_payouts AS payouts"
                . " ?p"
                . " WHERE ?p AND ?p",
                $this->buildQueryFields($fields),
                $join,
                $condition, $date_condition
            );

            $withdrawals = $this->getWithCache($withdrawals_query, 'balance');

            $amount -= $withdrawals['withdrawals_summary'];

            $total_period = $this->getTransactionsPeriod();

            if ($total_period['time_from'] && isset($params['time_from']) && $total_period['time_from'] < $params['time_from']) {
                $withdrawals_carried_forward_query = $this->db->quote(
                    "SELECT ?p"
                    . " FROM ?:vendor_payouts AS payouts"
                    . " ?p"
                    . " WHERE ?p AND payouts.payout_date < ?i",
                    $this->buildQueryFields($fields),
                    $join,
                    $condition, $params['time_from']
                );

                $withdrawals = $this->getWithCache($withdrawals_carried_forward_query, 'balance_cf');

                $amount_carried_forward -= $withdrawals['withdrawals_summary'];
            }
        }

        $amount = $this->roundRateValue($amount);
        $amount_carried_forward = $this->roundRateValue($amount_carried_forward);

        /**
         * Executes after the balance is calculated,
         * allows to modify calculated values.
         *
         * @param VendorPayouts $this                   VendorPayouts instance
         * @param array         $params                 Search parameters
         * @param float         $amount                 Balance
         * @param float|null    $amount_carried_forward Balance carried forward
         */
        fn_set_hook('vendor_payouts_get_balance_post', $this, $params, $amount, $amount_carried_forward);

        return array($amount, $amount_carried_forward);
    }

    /**
     * Provides ID of the current vendor (0 for the admin).
     *
     * @return int Vendor ID
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * Gets results from cache or from the DB and caches them.
     *
     * @param string $query        SQL query text
     * @param string $cache_prefix Cache prefix
     *
     * @return array Results
     */
    protected function getWithCache($query, $cache_prefix)
    {
        $cache_prefix = 'vendor_payouts_' . fn_uncamelize($cache_prefix);
        $cache_key = md5($query);

        Registry::registerCache(
            array($cache_prefix, $cache_key),
            array('orders', 'vendor_payouts', 'companies', 'statuses'),
            Registry::cacheLevel('static'),
            true
        );

        if (!$result = Registry::get($cache_key)) {
            $result = $this->db->getRow($query);
            Registry::set($cache_key, $result);
        }

        return $result;
    }

    /**
     * Rounds rate value accordingly to primary currency settings.
     * This function is used to prevent float operations rounding errors, e.g.
     *
     * @param float|null $value Value to round
     *
     * @return float|null
     */
    protected function roundRateValue($value)
    {
        if ($value === null) {
            return $value;
        }

        if ($this->currency === null) {
            $currencies = Registry::get('currencies');
            $this->currency = $currencies[CART_PRIMARY_CURRENCY];
        }

        return fn_format_rate_value($value, 'F', $this->currency['decimals'], '.', '');
    }
}
