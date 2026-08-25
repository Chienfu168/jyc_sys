-- 捐贈屬性:區分「樂捐款」(現金)與「樂捐實物」,收據依此顯示對應欄位。
ALTER TABLE donations
  ADD COLUMN donation_kind ENUM('cash', 'in_kind') NOT NULL DEFAULT 'cash' AFTER amount,
  ADD COLUMN in_kind_item VARCHAR(255) NULL AFTER donation_kind;
