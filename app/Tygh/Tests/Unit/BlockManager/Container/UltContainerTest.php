<?php

namespace Tygh\Tests\Unit\BlockManager\Container;

class UltContainerTest extends ContainerTestGeneral
{
    protected $productEdition = 'ULTIMATE';

    public function getTestData()
    {
        return array(
            // default page
            array(1, array(
                'container_id' => '1',
                'location_id' => '1',
                'position' => 'HEADER',
                'width' => '16',
                'user_class' => 'header-grid',
                'linked_to_default' => 'Y',
                'status' => 'A',
                'default' => '1',
            ), array(

            ), array(
                'uses_default_content' => false,
                'has_displayable_content' => true,
                'can_be_reset_to_default' => false,
                'linked_message' => false
            )),

            // non-default page, linked container
            array(1, array(
                'container_id' => '2',
                'location_id' => '3',
                'position' => 'HEADER',
                'width' => '16',
                'user_class' => '',
                'linked_to_default' => 'Y',
                'status' => 'A',
            ), array(

            ), array(
                'uses_default_content' => true,
                'has_displayable_content' => false,
                'can_be_reset_to_default' => false,
                'linked_message' => 'container_not_used'
            )),

            // non-default page, CONTENT
            array(1, array(
                'container_id' => '3',
                'location_id' => '3',
                'position' => 'CONTENT',
                'width' => '16',
                'user_class' => 'content-grid',
                'linked_to_default' => 'Y',
                'status' => 'A',
            ), array(

            ), array(
                'uses_default_content' => false,
                'has_displayable_content' => true,
                'can_be_reset_to_default' => false,
                'linked_message' => false
            )),

            // non-default page, custom container
            array(1, array(
                'container_id' => '4',
                'location_id' => '3',
                'position' => 'HEADER',
                'width' => '16',
                'user_class' => '',
                'linked_to_default' => 'N',
                'status' => 'A',
            ), array(

            ), array(
                'uses_default_content' => false,
                'has_displayable_content' => true,
                'can_be_reset_to_default' => true,
                'linked_message' => false
            )),

            // dynamic object, HEADER
            array(1, array(
                'container_id' => '10',
                'location_id' => '3',
                'position' => 'HEADER',
                'width' => '16',
                'user_class' => '',
                'linked_to_default' => 'N',
                'status' => 'A',
            ), array(
                'object_type' => 'products',
                'object_id' => '12',
            ), array(
                'uses_default_content' => false,
                'has_displayable_content' => true,
                'can_be_reset_to_default' => false,
                'linked_message' => false
            )),

            // dynamic object, CONTENT
            array(1, array(
                'container_id' => '6',
                'location_id' => '3',
                'position' => 'CONTENT',
                'width' => '16',
                'user_class' => 'content-grid',
                'linked_to_default' => 'Y',
                'status' => 'A',
                'company_id' => '1',
            ), array(
                'object_type' => 'products',
                'object_id' => '12',
            ), array(
                'uses_default_content' => false,
                'has_displayable_content' => true,
                'can_be_reset_to_default' => false,
                'linked_message' => false
            )),
        );
    }
}
