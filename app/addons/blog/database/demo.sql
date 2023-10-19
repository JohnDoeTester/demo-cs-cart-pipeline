REPLACE INTO ?:pages (page_id, parent_id, id_path, status, page_type, position, timestamp, new_window) VALUES ('7', '0', '7', 'A', 'B', '0', '1415336000', '0');
REPLACE INTO ?:pages (page_id, parent_id, id_path, status, page_type, position, timestamp, new_window) VALUES ('8', '7', '7/8', 'A', 'B', '0', '1415316000', '0');

REPLACE INTO ?:blog_authors (page_id, user_id) VALUES (7, 1);
REPLACE INTO ?:blog_authors (page_id, user_id) VALUES (8, 1);

REPLACE INTO ?:images (`image_id`, `image_path`, `image_x`, `image_y`)
VALUES
  (1074, '1.png', 894, 305);


REPLACE INTO ?:images_links (`pair_id`, `object_id`, `object_type`, `image_id`, `detailed_id`, `type`, `position`)
VALUES
  (953, 8, 'blog', 1074, 0, 'M', 0);