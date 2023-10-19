REPLACE INTO ?:banner_descriptions (`banner_id`, `banner`, `url`, `description`, `lang_code`) VALUES(7, 'Gift certificate', 'gift_certificates.add', '', 'en');
REPLACE INTO ?:banner_descriptions (`banner_id`, `banner`, `url`, `description`, `lang_code`) VALUES(9, 'Discount if select pickup', 'index.php?dispatch=pages.view&page_id=20', '', 'en');
REPLACE INTO ?:banner_images (`banner_image_id`, `banner_id`, `lang_code`) VALUES(40, 9, 'en');

REPLACE INTO `cscart_images` (`image_id`, `image_path`, `image_x`, `image_y`) VALUES (8270,'mve_o1aa-uz.jpg',1200,500);
REPLACE INTO `cscart_images` (`image_id`, `image_path`, `image_x`, `image_y`) VALUES (8277,'acme.jpg',1200,500);
REPLACE INTO `cscart_images` (`image_id`, `image_path`, `image_x`, `image_y`) VALUES (8278,'stark.jpg',1200,500);
REPLACE INTO `cscart_images` (`image_id`, `image_path`, `image_x`, `image_y`) VALUES (8279,'bronze.jpg',1200,500);
REPLACE INTO `cscart_images` (`image_id`, `image_path`, `image_x`, `image_y`) VALUES (8290,'banner_bronze.png',263,367);


REPLACE INTO `cscart_images_links` (`pair_id`, `object_id`, `object_type`, `image_id`, `detailed_id`, `type`, `position`) VALUES (6288,42,'promo',8270,0,'M',0);
REPLACE INTO `cscart_images_links` (`pair_id`, `object_id`, `object_type`, `image_id`, `detailed_id`, `type`, `position`) VALUES (6295,43,'promo',8277,0,'M',0);
REPLACE INTO `cscart_images_links` (`pair_id`, `object_id`, `object_type`, `image_id`, `detailed_id`, `type`, `position`) VALUES (6296,44,'promo',8278,0,'M',0);
REPLACE INTO `cscart_images_links` (`pair_id`, `object_id`, `object_type`, `image_id`, `detailed_id`, `type`, `position`) VALUES (6297,45,'promo',8279,0,'M',0);
REPLACE INTO `cscart_images_links` (`pair_id`, `object_id`, `object_type`, `image_id`, `detailed_id`, `type`, `position`) VALUES (6308,47,'promo',8290,0,'M',0);

UPDATE ?:banner_descriptions SET banner='Welcome to Multi-Vendor demo marketplace', url='index.php?dispatch=pages.view&page_id=24', description='' WHERE banner_id=6;
UPDATE ?:banner_descriptions SET banner='Acme Corporation: Join our loyalty program to get special prices', url='index.php?dispatch=pages.view&page_id=25', description='' WHERE banner_id=8;
UPDATE ?:banner_descriptions SET banner='Become our vendor with no transaction fee', url='index.php?dispatch=pages.view&page_id=27', description='' WHERE banner_id=16;
UPDATE ?:banner_descriptions SET banner='No transaction fee', url='companies.apply_for_vendor&plan_id=2', description='' WHERE banner_id=18;
UPDATE ?:banner_descriptions SET url='index.php?dispatch=products.view&product_id=248' WHERE banner_id=17;
UPDATE ?:banner_descriptions SET banner='X-Box Mobile', url='index.php?dispatch=products.view&product_id=248', description='' WHERE banner_id=20;
UPDATE ?:banner_descriptions SET banner='Acme Mobile', url='index.php?dispatch=pages.view&page_id=25', description='' WHERE banner_id=21;
UPDATE ?:banner_descriptions SET banner='Multivendor demo mobile', url='index.php?dispatch=pages.view&page_id=24', description='' WHERE banner_id=22;

UPDATE ?:images_links SET image_id=8270 WHERE object_type='promo' AND object_id IN (SELECT banner_image_id FROM ?:banner_images WHERE banner_id=6);
UPDATE ?:images_links SET image_id=8277 WHERE object_type='promo' AND object_id IN (SELECT banner_image_id FROM ?:banner_images WHERE banner_id=8);
UPDATE ?:images_links SET image_id=8279 WHERE object_type='promo' AND object_id IN (SELECT banner_image_id FROM ?:banner_images WHERE banner_id=16);
UPDATE ?:images_links SET image_id=8290 WHERE object_type='promo' AND object_id IN (SELECT banner_image_id FROM ?:banner_images WHERE banner_id=18);
UPDATE ?:images_links SET image_id=8632 WHERE object_type='promo' AND object_id IN (SELECT banner_image_id FROM ?:banner_images WHERE banner_id=20);
UPDATE ?:images_links SET image_id=8633 WHERE object_type='promo' AND object_id IN (SELECT banner_image_id FROM ?:banner_images WHERE banner_id=21);
UPDATE ?:images_links SET image_id=8634 WHERE object_type='promo' AND object_id IN (SELECT banner_image_id FROM ?:banner_images WHERE banner_id=22);

REPLACE INTO `cscart_bm_block_statuses` (`snapping_id`, `object_ids`, `object_type`)
  VALUES  ((SELECT `snapping_id` FROM `cscart_bm_snapping` WHERE `user_class` = 'block__no_transaction_fee' LIMIT 1), '27', 'pages');

REPLACE INTO `cscart_bm_block_statuses` (`snapping_id`, `object_ids`, `object_type`)
  VALUES  ((SELECT `snapping_id` FROM `cscart_bm_snapping` WHERE `user_class` = 'products__acme_corporation' LIMIT 1), '25', 'pages');