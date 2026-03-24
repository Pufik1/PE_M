-- SQL-скрипт для обновления базы данных
-- Добавление поля comment в таблицу orders

USE polesie_erp;

-- Проверяем и добавляем поле comment, если оно отсутствует
ALTER TABLE orders ADD COLUMN IF NOT EXISTS comment TEXT;
