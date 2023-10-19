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

use Tygh\Addons\VendorRating\Enum\Logging;
use Tygh\Addons\VendorRating\Exception\CalculationException;
use Tygh\Addons\VendorRating\Exception\UnknownVariableException;
use Tygh\Addons\VendorRating\Exception\VariableCountException;
use Tygh\Addons\VendorRating\ServiceProvider;
use Tygh\Common\OperationResult;
use Tygh\Enum\VendorStatuses;

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'validate_formula') {
        /** @var \Tygh\Ajax $ajax */
        $ajax = Tygh::$app['ajax'];

        $formula = isset($_REQUEST['formula'])
            ? trim($_REQUEST['formula'])
            : '';

        $is_valid = false;
        $error_message = '';
        if ($formula !== '') {
            $criteria = ServiceProvider::getCriteriaSchema();
            $dummy_variables = [];
            foreach ($criteria as $criterion) {
                $dummy_variables[$criterion['variable_name']] = 1;
            }

            $calculator = ServiceProvider::getCalculator();
            try {
                $calculator->calculate($formula, $dummy_variables);
                $is_valid = true;
            } catch (UnknownVariableException $e) {
                $error_message = __(
                    'vendor_rating.unknown_variable',
                    [
                        '[variable]' => $e->getVariable(),
                    ]
                );
            } catch (DivisionByZeroError $e) {
                $error_message = __(
                    'vendor_rating.division_by_zero'
                );
            } catch (Exception $e) {
            }
        }

        $ajax->assign('is_valid', $is_valid);
        $ajax->assign('error_message', $error_message);
        exit(0);
    }

    if ($mode === 'recalculate') {
        if (isset($_REQUEST['company_id'])) {
            $company_ids = [(int) $_REQUEST['company_id']];
        } else {
            $company_ids = $companies = db_get_fields('SELECT company_id FROM ?:companies WHERE status = ?s', VendorStatuses::ACTIVE);
        }

        $service = ServiceProvider::getVendorService();
        $results = [];

        foreach ($company_ids as $company_id) {
            $result = new OperationResult(false);

            $company_name = fn_get_company_name($company_id);
            $result->setData($company_id, 'company_id');
            $result->setData($company_name, 'company_name');

            $previous_rating = $current_rating = $service->getAbsouluteRating($company_id);
            $result->setData($previous_rating, 'previous_rating');

            try {
                $rating = $service->calculateAbsoluteRating($company_id);
                $current_rating = $service->setAbsouluteRating($company_id, $rating);
                $result->setSuccess(true);
            } catch (CalculationException $e) {
                $result->addError(
                    $e->getCode(),
                    __(
                        'vendor_rating.wrong_formula',
                        [
                            '[error]' => $e->getMessage(),
                        ]
                    )
                );
            } catch (VariableCountException $e) {
                $result->addError(
                    $e->getCode(),
                    __(
                        'vendor_rating.too_many_variables',
                        [
                            '[allowed_variables_count]' => $e->getAllowedVariablesCount(),
                            '[passed_variables_count]'  => $e->getPassedVariablesCount(),
                        ]
                    )
                );
            } catch (UnknownVariableException $e) {
                $result->addError(
                    $e->getCode(),
                    __(
                        'vendor_rating.unknown_variable',
                        [
                            '[variable]' => $e->getVariable(),
                        ]
                    )
                );
            } catch (DivisionByZeroError $e) {
                $result->addError(
                    $e->getCode(),
                    __('vendor_rating.division_by_zero')
                );
            }

            $result->setData($current_rating, 'rating');

            if ($result->isSuccess()) {
                $result->addMessage(
                    0,
                    __('vendor_rating.company_rating', ['[company]' => $company_name, '[rating]' => $current_rating])
                );
                fn_log_event(Logging::LOG_TYPE_VENDOR_RATING, Logging::ACTION_SUCCESS, ['result' => $result]);
            } else {
                fn_log_event(Logging::LOG_TYPE_VENDOR_RATING, Logging::ACTION_FAILURE, ['result' => $result]);
            }

            if (defined('CONSOLE') || isset($_REQUEST['company_id'])) {
                $result->showNotifications();
            }

            $results[] = $result;

            /** @var \Tygh\SmartyEngine\Core $view */
            $view = Tygh::$app['view'];

            $view->assign('results', $results);
        }
    }

    return [CONTROLLER_STATUS_OK];
}
