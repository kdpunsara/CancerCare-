-- Run once after selecting the existing Cancer Care database.
-- Existing pharmacist accounts remain usable; new accounts are explicitly created as pending.
ALTER TABLE Pharmacist
    ADD COLUMN initial_inventory_completed TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN initial_inventory_completed_at DATETIME NULL DEFAULT NULL;
