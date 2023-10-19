<?php

namespace Tygh\Tests\Unit\BlockManager\Container;

class MveContainerTest extends ContainerTestGeneral
{
    protected $productEdition = 'MULTIVENDOR';

    public function getTestData()
    {
        return array(
            // admin, default page
            array(0, array(
                'container_id' => '1',
                'location_id' => '1',
                'position' => 'HEADER',
                'width' => '16',
                'user_class' => 'header-grid',
                'linked_to_default' => 'Y',
                'status' => 'A',
                'company_id' => '0',
                'default' => '1',
            ), array(

            ), array(
                'uses_default_content' => false,
                'has_displayable_content' => true,
                'can_be_reset_to_default' => false,
                'linked_message' => false
            )),

            // admin, non-default page, linked container
            array(0, array(
                'container_id' => '2',
                'location_id' => '3',
                'position' => 'HEADER',
                'width' => '16',
                'user_class' => '',
                'linked_to_default' => 'Y',
                'status' => 'A',
                'company_id' => '0',
            ), array(

            ), array(
                'uses_default_content' => true,
                'has_displayable_content' => false,
                'can_be_reset_to_default' => false,
                'linked_message' => 'container_not_used'
            )),

            // admin, non-default page, CONTENT
            array(0, array(
                'container_id' => '3',
                'location_id' => '3',
                'position' => 'CONTENT',
                'width' => '16',
                'user_class' => 'content-grid',
                'linked_to_default' => 'Y',
                'status' => 'A',
                'company_id' => '0',
            ), array(

            ), array(
                'uses_default_content' => false,
                'has_displayable_content' => true,
                'can_be_reset_to_default' => false,
                'linked_message' => false
            )),

            // admin, non-default page, custom container
            array(0, array(
                'container_id' => '4',
                'location_id' => '3',
                'position' => 'HEADER',
                'width' => '16',
                'user_class' => '',
                'linked_to_default' => 'N',
                'status' => 'A',
                'company_id' => '0',
            ), array(

            ), array(
                'uses_default_content' => false,
                'has_displayable_content' => true,
                'can_be_reset_to_default' => true,
                'linked_message' => false
            )),

            // admin, dynamic object
            array(0, array(
                'container_id' => '4',
                'location_id' => '3',
                'position' => 'HEADER',
                'width' => '16',
                'user_class' => '',
                'linked_to_default' => 'Y',
                'status' => 'A',
                'company_id' => '0',
            ), array(
                'object_type' => 'products',
                'object_id' => '12',
            ), array(
                'uses_default_content' => false,
                'has_displayable_content' => true,
                'can_be_reset_to_default' => false,
                'linked_message' => false
            )),

            // vendor, non-default page w/o vendor container
            array(1, array(
                'container_id' => '5',
                'location_id' => '3',
                'position' => 'CONTENT',
                'width' => '16',
                'user_class' => 'content-grid',
                'linked_to_default' => 'Y',
                'status' => 'A',
                'company_id' => '0',
            ), array(

            ), array(
                'uses_default_content' => true,
                'has_displayable_content' => false,
                'can_be_reset_to_default' => false,
                'linked_message' => 'mve.container_not_used'
            )),

            // vendorm non-default page w/ vendor container
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

            ), array(
                'uses_default_content' => false,
                'has_displayable_content' => true,
                'can_be_reset_to_default' => true,
                'linked_message' => false
            )),

            // vendor, dynamic object w/o vendor container
            array(1, array(
                'container_id' => '7',
                'location_id' => '3',
                'position' => 'CONTENT',
                'width' => '16',
                'user_class' => 'content-grid',
                'linked_to_default' => 'Y',
                'status' => 'A',
                'company_id' => '0',
            ), array(
                'object_type' => 'products',
                'object_id' => '12',
            ), array(
                'uses_default_content' => true,
                'has_displayable_content' => false,
                'can_be_reset_to_default' => false,
                'linked_message' => 'mve.container_not_used'
            )),

            // vendor, dynamic object w/ vendor container
            array(1, array(
                'container_id' => '8',
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
                'can_be_reset_to_default' => true,
                'linked_message' => false
            )),
        );
    }
}
