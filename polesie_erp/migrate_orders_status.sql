-- Миграция для обновления поля status в таблице orders
ALTER TABLE orders 
MODIFY COLUMN status ENUM('new', 'processing', 'in_progress', 'ready', 'shipped', 'closed', 'cancelled') DEFAULT 'new';
