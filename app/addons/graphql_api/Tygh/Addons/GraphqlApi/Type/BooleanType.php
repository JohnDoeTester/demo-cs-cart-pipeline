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

namespace Tygh\Addons\GraphqlApi\Type;

use GraphQL\Language\AST\BooleanValueNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\BooleanType as BaseType;
use Tygh\Enum\YesNo;

class BooleanType extends BaseType
{
    public function serialize($value)
    {
        return YesNo::toBool($value);
    }

    public function parseLiteral($valueNode, ?array $variables = null)
    {
        if ($valueNode instanceof BooleanValueNode || $valueNode instanceof StringValueNode) {
            return YesNo::toBool($valueNode->value);
        }

        return parent::parseLiteral($valueNode, $variables);
    }
}
