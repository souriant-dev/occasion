USE occasion_db;

ALTER TABLE products
  ADD COLUMN seller_stars TINYINT UNSIGNED DEFAULT NULL,
  ADD CONSTRAINT chk_products_seller_stars
    CHECK (seller_stars IS NULL OR seller_stars BETWEEN 1 AND 5);
