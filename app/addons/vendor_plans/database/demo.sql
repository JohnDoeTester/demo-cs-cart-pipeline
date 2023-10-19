REPLACE INTO `?:vendor_plans` (`plan_id`, `status`, `position`, `is_default`, `price`, `periodicity`, `commission`, `products_limit`, `revenue_limit`, `vendor_store`, `categories`)
VALUES
    (1,'A',20,0,100.00,'month',10.00,1000,1000.00,1,''),
    (2,'A',10,0,0.00,'month',20.00,25,500.00,0,''),
    (3,'A',30,1,250.00,'month',5.00,10000,10000.00,1,''),
    (4,'A',40,0,1000.00,'month',1.00,100000,0.00,1,''),
    (5,'H',0,0,200.00,'month',4.50,12500,15000.00,1,'');

UPDATE ?:companies SET plan_id = 2;
UPDATE ?:companies SET plan_id = 3 WHERE company_id = 1;
