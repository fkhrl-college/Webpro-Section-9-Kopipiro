CREATE TABLE users (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer') NOT NULL DEFAULT 'customer'
);

CREATE TABLE products (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    price INT NOT NULL,
    url_image VARCHAR(2048) NOT NULL,
    category ENUM('kopi', 'non-kopi') NOT NULL DEFAULT 'kopi'
);

-- Seed kopi
INSERT INTO products (name, price, url_image) VALUES
    (
        'Cappuccino',
        25000,
        'https://images.unsplash.com/photo-1506372023823-741c83b836fe?w=1200&auto=format&fit=crop&q=60'
    ),
    (
        'Americano',
        15000,
        'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?w=1200&auto=format&fit=crop&q=60'
    ),
    (
        'latte',
        20000,
        'https://plus.unsplash.com/premium_photo-1673545518947-ddf3240090b1?q=80&w=687&auto=format&fit=crop'
    ),
    (
        'Butterscotch',
        30000,
        'https://plus.unsplash.com/premium_photo-1723759448747-1d174225e61f?q=80&w=1170&auto=format&fit=crop'
    );

-- Seed non-kopi
INSERT INTO products (name, price, url_image, category) VALUES
    (
        'Original Tea',
        3000,
        'https://images.unsplash.com/photo-1617543414010-212c4537604d?q=80&w=873&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=NHwxMjA3fDA%3D',
        'non-kopi'
    ),
    (
        'Toast Bread',
        12000,
        'https://images.unsplash.com/photo-1612827788868-c8632040ab64?q=80&w=870&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=NHwxMjA3fDA%3D',
        'non-kopi'
    ),
    (
        'Water Bottle',
        4000,
        'https://images.unsplash.com/photo-1616118132534-381148898bb4?q=80&w=764&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=NHwxMjA3fDA%3D',
        'non-kopi'
    ),
    (
        'Matcha Latte',
        18000,
        'https://images.unsplash.com/photo-1515823064-d6e0c04616a7?w=1200&auto=format&fit=crop&q=60',
        'non-kopi'
    ),
    (
        'Chocolate Milk',
        15000,
        'https://images.unsplash.com/photo-1517578239113-b03992dcdd25?w=1200&auto=format&fit=crop&q=60',
        'non-kopi'
    );