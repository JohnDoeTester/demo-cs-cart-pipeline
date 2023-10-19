<?php

// Mock "fn_get_schema()" function
namespace Tygh\BlockManager {

    function fn_get_schema()
    {
        $path_name = __DIR__ . '/../../../../../_tools/unit_tests/files_for_tests/schemas/block_manager/fillings.php';

        if (file_exists($path_name)) {
            return include($path_name);
        } else {
            return array();
        }
    }
}

namespace {

    use Tygh\BlockManager\SchemesManager;
    use PHPUnit\Framework\TestCase;

    class SchemesManagerTest extends TestCase
    {
        /**
         *
         * @dataProvider providerGetSchemes()
         */
        public function testPrepareContent($block_scheme, $request_params, $result)
        {
            $this->assertEquals($result, SchemesManager::prepareContent($block_scheme, $request_params));
        }

        public function providerGetSchemes()
        {
            return array(

                // test 1
                array(array(), array(), array()),

                // test 2
                array(
                    array('content' => array()),
                    array(),
                    array()
                ),

                // test 3
                array (
                    array(
                        'content' => array(
                            'items' => array()
                        ),
                    ),
                    array(),
                    array(
                        'items' => array()
                    )
                ),

                // test 4 - default filling
                array(
                    array(
                        'content' => array(
                            'items' => array(
                                'fillings' => array(
                                    'manually' => array(
                                        'picker' => 'addons/banners/pickers/banners/picker.tpl',
                                        'picker_params' => array(
                                            'type' => 'links',
                                        ),
                                        'params' => array(
                                            'sort_by' => 'position',
                                            'sort_order' => 'asc',
                                        ),
                                    ),
                                    'newest' => array(
                                        'params' => array(
                                            'sort_by' => 'timestamp',
                                            'sort_order' => 'desc',
                                        ),

                                    ),
                                )
                            )
                        ),
                    ),
                    array(),
                    array(
                        'items' => array(
                            'fillings' => array(
                                'manually' => array(
                                    'picker' => 'addons/banners/pickers/banners/picker.tpl',
                                    'picker_params' => array(
                                        'type' => 'links',
                                    ),
                                    'params' => array(
                                        'sort_by' => 'position',
                                        'sort_order' => 'asc',
                                    ),
                                ),
                                'newest' => array(
                                    'params' => array(
                                        'sort_by' => 'timestamp',
                                        'sort_order' => 'desc',
                                    ),
                                    'settings' => array(
                                        'period' => array (
                                            'type' => 'selectbox',
                                            'values' => array (
                                                'A' => 'any_date',
                                                'D' => 'today',
                                                'HC' => 'last_days',
                                            ),
                                            'default_value' => 'any_date'
                                        ),
                                        'last_days' => array (
                                            'type' => 'input',
                                            'default_value' => 1
                                        ),
                                        'limit' => array (
                                            'type' => 'input',
                                            'default_value' => 3
                                        ),
                                        'cid' => array (
                                            'type' => 'picker',
                                            'option_name' => 'filter_by_categories',
                                            'picker' => 'pickers/categories/picker.tpl',
                                            'picker_params' => array(
                                                'multiple' => true,
                                                'use_keys' => 'N',
                                                'view_mode' => 'table',
                                                'no_item_text' => 'No filter specified. Filter by location set as default.',
                                            ),
                                            'unset_empty' => true, // remove this parameter from params list if the value is empty
                                        ),
                                    )
                                ),
                            )
                        )
                    )
                ),

                // test 5 - ignore setting cid
                array(
                    array('content' =>
                        array(
                            'items' => array(
                                'fillings' => array(
                                    'newest' => array(
                                        'params' => array(
                                            'sort_by' => 'timestamp',
                                            'sort_order' => 'desc',
                                            'ignore_settings' => array(
                                                '0' => 'cid',
                                            ),
                                        ),
                                    ),
                                )
                            )
                        ),
                    ),
                    array(),
                    array(
                        'items' => array(
                            'fillings' => array(
                                'newest' => array(
                                    'params' => array(
                                        'sort_by' => 'timestamp',
                                        'sort_order' => 'desc',
                                        'ignore_settings' => array(
                                            '0' => 'cid',
                                        ),
                                    ),
                                    'settings' => array(
                                        'period' => array (
                                            'type' => 'selectbox',
                                            'values' => array (
                                                'A' => 'any_date',
                                                'D' => 'today',
                                                'HC' => 'last_days',
                                            ),
                                            'default_value' => 'any_date'
                                        ),
                                        'last_days' => array (
                                            'type' => 'input',
                                            'default_value' => 1
                                        ),
                                        'limit' => array (
                                            'type' => 'input',
                                            'default_value' => 3
                                        ),
                                    )
                                ),
                            )
                        )
                    )
                ),

                // end tests
            );
        }
    }
}