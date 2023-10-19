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

namespace Tygh\Addons\GraphqlApi;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as BaseType;

class Type extends ObjectType
{
    protected static $types;

    public static function resolveType($type)
    {
        if ($type instanceof BaseType) {
            return $type;
        }

        if (isset(static::$types[$type])) {
            return static::$types[$type];
        }

        if (class_exists($type)) {
            return static::$types[$type] = new $type;
        }

        return static::$types[$type] = new Type(static::getTypeConfig($type));
    }

    /**
     * @param \GraphQL\Type\Definition\Type|string $type
     *
     * @return \GraphQL\Type\Definition\ListOfType
     */
    public static function listOf($type)
    {
        return new ListOfType(static::resolveType($type));
    }

    /**
     * @param \GraphQL\Type\Definition\Type|string $type
     *
     * @return \GraphQL\Type\Definition\NonNull
     */
    public static function nonNull($type)
    {
        return new NonNull(static::resolveType($type));
    }

    protected static function getTypeConfig($type)
    {
        $config = fn_get_schema('graphql_types', $type);

        if (!$config) {
            //TODO throw not found exception
        }

        return $config;
    }
}
