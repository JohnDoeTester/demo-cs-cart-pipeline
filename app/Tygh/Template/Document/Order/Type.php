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

namespace Tygh\Template\Document\Order;


use Tygh\Database\Connection;
use Tygh\Exceptions\InputException;
use Tygh\Template\Document\Document;
use Tygh\Template\Document\IIncludableType;
use Tygh\Template\Document\IPreviewableType;
use Tygh\Template\Document\IType;
use Tygh\Template\Document\Repository;
use Tygh\Template\Renderer;
use Tygh\Template\VariableCollectionFactory;
use Tygh\Tools\SecurityHelper;

/**
 * The class that implements the `order` document type.
 *
 * @package Tygh\Template\Document\Order
 */
class Type implements IType, IPreviewableType, IIncludableType
{
    const DOCUMENT_TYPE = 'order';

    /** @var Repository */
    protected $repository;

    /** @var Connection */
    protected $connection;

    /** @var Renderer */
    protected $renderer;

    /** @var VariableCollectionFactory */
    protected $collection_factory;

    /**
     * Order document type constructor.
     *
     * @param Repository                        $repository            Instance of document repository.
     * @param Connection                        $connection            Instance of database connection.
     * @param Renderer                          $renderer              Instance of template renderer.
     * @param VariableCollectionFactory         $collection_factory    Instance of variable collection factory.
     */
    public function __construct(
        Repository $repository,
        Connection $connection,
        Renderer $renderer,
        VariableCollectionFactory $collection_factory
    )
    {
        $this->repository = $repository;
        $this->connection = $connection;
        $this->renderer = $renderer;
        $this->collection_factory = $collection_factory;
    }

    /**
     * @inheritDoc
     */
    public function getCode()
    {
        return self::DOCUMENT_TYPE;
    }

    /**
     * @inheritDoc
     */
    public function preview(Document $document)
    {
        $condition = fn_get_company_condition('?:orders.company_id');
        $order_id = (int) $this->connection->getField('SELECT MIN(order_id) FROM ?:orders WHERE parent_order_id = 0 ?p', $condition);

        if (empty($order_id)) {
            throw new InputException(__("document_preview_order_not_found"));
        }
        $order = new Order($order_id, DESCR_SL, CART_SECONDARY_CURRENCY);

        return $this->render($order, $document);
    }

    /**
     * @inheritDoc
     */
    public function includeDocument($code, $lang_code, $params)
    {
        $order_id = array_shift($params);
        return $this->renderById($order_id, $code, $lang_code);
    }

    /**
     * Render order document.
     *
     * @param Order    $order    Instance of order.
     * @param Document $document Instance of document template.
     * @param string   $area     One-letter site area identifier
     *
     * @return string
     */
    public function render(Order $order, Document $document, $area = AREA)
    {
        $context = new Context($order, $area);
        $variable_collection = $this->collection_factory->createCollection(self::SCHEMA_DIR, $this->getCode(), $context);

        return $this->renderer->renderTemplate($document, $context, $variable_collection);
    }

    /**
     * Render order document by order identifier.
     *
     * @param int    $order_id      Order identifier.
     * @param string $code          Template code.
     * @param string $lang_code     Language code.
     * @param string $currency_code Currency code
     * @param string $area          One-letter site area identifier
     * @param array  $order_params  Additional order parameters
     *
     * @return string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    public function renderById($order_id, $code, $lang_code, $currency_code = '', $area = AREA, array $order_params = [])
    {
        $order = new Order($order_id, $lang_code, $currency_code, $order_params);
        $document = $this->repository->findByTypeAndCode($this->getCode(), $code);

        if ($order->data && $document) {
            $order->data['notes'] = SecurityHelper::sanitizeHtml($order->data['notes']);
            $order->data['details'] = SecurityHelper::sanitizeHtml($order->data['details']);
            return $this->render($order, $document, $area);
        }

        return '';
    }
}