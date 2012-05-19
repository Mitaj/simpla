ALTER TABLE  `s_products` ADD `featured` TINYINT( 1 ) NULL DEFAULT NULL;
ALTER TABLE  `s_pages` ADD  `header` VARCHAR( 1024 ) NOT NULL ;
UPDATE `s_pages` SET  `header`=`name`;
SELECT @t:= 1+MAX(position) FROM s_products;
UPDATE s_products SET position = @t-position;