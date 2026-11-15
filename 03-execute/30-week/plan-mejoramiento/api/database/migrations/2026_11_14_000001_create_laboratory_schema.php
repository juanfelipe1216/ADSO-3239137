<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE SCHEMA lab_equipment;
CREATE TABLE lab_equipment.category (
    id uuid PRIMARY KEY,
    name varchar(120) NOT NULL UNIQUE
);
CREATE TABLE lab_equipment.equipment (
    id uuid PRIMARY KEY,
    name varchar(250) NOT NULL,
    manufacturer varchar(200) NOT NULL,
    reference varchar(64) NOT NULL,
    category_id uuid NOT NULL REFERENCES lab_equipment.category(id) ON DELETE RESTRICT,
    total_units integer NOT NULL,
    available_units integer NOT NULL,
    cover_key varchar(512),
    deleted_at timestamptz,
    CONSTRAINT ck_equipment_available_range CHECK (available_units >= 0 AND available_units <= total_units),
    CONSTRAINT ck_equipment_reference CHECK (reference ~ '^[A-Z0-9][A-Z0-9._/-]{0,63}$'),
    CONSTRAINT ck_equipment_cover_key CHECK (cover_key IS NULL OR length(btrim(cover_key)) > 0)
);
CREATE UNIQUE INDEX uq_equipment_active_reference ON lab_equipment.equipment(reference) WHERE deleted_at IS NULL;
CREATE INDEX ix_equipment_category_name ON lab_equipment.equipment(category_id, name) WHERE deleted_at IS NULL;
CREATE TABLE lab_equipment.researcher (
    id uuid PRIMARY KEY,
    full_name varchar(200) NOT NULL,
    document_number varchar(30) NOT NULL UNIQUE
);
CREATE TABLE lab_equipment."user" (
    id uuid PRIMARY KEY,
    username varchar(120) NOT NULL UNIQUE,
    password_hash varchar(512) NOT NULL,
    role varchar(40) NOT NULL,
    CONSTRAINT ck_user_username CHECK (length(username) > 0 AND username = lower(btrim(username)))
);
CREATE TABLE lab_equipment.loan (
    id uuid PRIMARY KEY,
    researcher_id uuid NOT NULL REFERENCES lab_equipment.researcher(id) ON DELETE RESTRICT,
    loaned_by_user_id uuid NOT NULL REFERENCES lab_equipment."user"(id) ON DELETE RESTRICT,
    loaned_at timestamptz NOT NULL
);
CREATE INDEX ix_loan_loaned_at ON lab_equipment.loan(loaned_at);
CREATE INDEX ix_loan_researcher ON lab_equipment.loan(researcher_id);
CREATE INDEX ix_loan_operator ON lab_equipment.loan(loaned_by_user_id);
CREATE TABLE lab_equipment.loan_item (
    id uuid PRIMARY KEY,
    loan_id uuid NOT NULL REFERENCES lab_equipment.loan(id) ON DELETE CASCADE,
    equipment_id uuid NOT NULL REFERENCES lab_equipment.equipment(id) ON DELETE RESTRICT,
    equipment_name varchar(250) NOT NULL,
    reference varchar(64) NOT NULL,
    category_name varchar(120) NOT NULL,
    due_at timestamptz NOT NULL,
    returned_at timestamptz,
    CONSTRAINT uq_loan_item_equipment UNIQUE(loan_id, equipment_id)
);
CREATE INDEX ix_loan_item_equipment ON lab_equipment.loan_item(equipment_id);
CREATE INDEX ix_loan_item_overdue ON lab_equipment.loan_item(due_at) WHERE returned_at IS NULL;
INSERT INTO lab_equipment.category (id, name) VALUES
('11111111-1111-4111-8111-111111111111', 'medición'),
('22222222-2222-4222-8222-222222222222', 'óptica'),
('33333333-3333-4333-8333-333333333333', 'electrónica'),
('44444444-4444-4444-8444-444444444444', 'química'),
('55555555-5555-4555-8555-555555555555', 'seguridad');
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP SCHEMA lab_equipment CASCADE');
    }
};
