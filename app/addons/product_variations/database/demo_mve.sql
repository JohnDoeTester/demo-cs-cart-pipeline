REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (278, 75.00, 0, 1, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (279, 75.00, 0, 1, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (280, 50.00, 0, 1, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (281, 75.00, 0, 1, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (282, 75.00, 0, 10, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (282, 75.00, 0, 5, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (282, 75.00, 0, 1, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (283, 75.00, 0, 10, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (283, 75.00, 0, 5, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (283, 75.00, 0, 1, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (284, 75.00, 0, 10, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (284, 75.00, 0, 5, 0);
REPLACE INTO ?:product_prices (`product_id`, `price`, `percentage_discount`, `lower_limit`, `usergroup_id`) VALUES (284, 75.00, 0, 1, 0);

SET @category_id = (SELECT category_id FROM cscart_categories WHERE category_id = 224 OR status = 'A' ORDER BY category_id = 224 DESC LIMIT 1);

REPLACE INTO ?:products_categories (`product_id`, `category_id`, `link_type`, `position`) VALUES (278, @category_id, 'M', 0);
REPLACE INTO ?:products_categories (`product_id`, `category_id`, `link_type`, `position`) VALUES (279, @category_id, 'M', 0);
REPLACE INTO ?:products_categories (`product_id`, `category_id`, `link_type`, `position`) VALUES (280, @category_id, 'M', 0);
REPLACE INTO ?:products_categories (`product_id`, `category_id`, `link_type`, `position`) VALUES (281, @category_id, 'M', 0);
REPLACE INTO ?:products_categories (`product_id`, `category_id`, `link_type`, `position`) VALUES (282, @category_id, 'M', 0);
REPLACE INTO ?:products_categories (`product_id`, `category_id`, `link_type`, `position`) VALUES (283, @category_id, 'M', 0);
REPLACE INTO ?:products_categories (`product_id`, `category_id`, `link_type`, `position`) VALUES (284, @category_id, 'M', 0);

UPDATE cscart_product_features SET company_id = 0;
UPDATE cscart_product_filters SET company_id = 0;