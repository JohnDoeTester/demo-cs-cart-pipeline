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


namespace Tygh\Template\Snippet\Table;

use Tygh\Template\IContext;

/**
 * The context class for an item represented in a table.
 *
 * @package Tygh\Template\Snippet\Table
 */
class ItemContext implements IContext
{
    /** @var IContext */
    protected $parent_context;

    /** @var mixed */
    protected $item;

    /** @var int */
    protected $counter;

    /**
     * ItemContext constructor.
     *
     * @param IContext          $context Instance of parent context.
     * @param array<string|int> $item    Item data.
     * @param int               $counter Sequential item counter.
     */
    public function __construct(IContext $context, $item, $counter = 0)
    {
        /**
         * Allows to change the table item context for the render of the data table snippet.
         *
         * @param self                    $this    Instance of current context
         * @param \Tygh\Template\IContext $context Instance of parent context
         * @param array<string|int>       $item    Item data
         * @param int                     $counter Sequential item counter
         */
        fn_set_hook('template_snippet_table_item_context_init', $this, $context, $item, $counter);

        $this->parent_context = $context;
        $this->counter = $counter;
        $this->item = $item;
    }

    /**
     * Gets item.
     *
     * @return mixed
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * Gets parent context.
     *
     * @return IContext
     */
    public function getParentContext()
    {
        return $this->parent_context;
    }
    
    /**
     * @inheritDoc
     */
    public function getLangCode()
    {
        return $this->parent_context->getLangCode();
    }

    /**
     * Fetches item's sequence counter
     *
     * @return int
     */
    public function getCounter()
    {
        return $this->counter;
    }

    /**
     * @inheritDoc
     */
    public function getArea()
    {
        return $this->parent_context->getArea();
    }
}
