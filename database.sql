DROP TABLE IF EXISTS damages;
DROP TABLE IF EXISTS thermal_units;
DROP TABLE IF EXISTS departments;

CREATE TABLE departments (
    id int PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    name varchar(255) UNIQUE NOT NULL
);

INSERT INTO departments (name) VALUES
    ('Филиал 1'),
    ('Филиал 2'),
    ('Филиал 3');

CREATE TABLE thermal_units (
    id int PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    department_id int REFERENCES departments (id),
    name varchar(255) NOT NULL
);

INSERT INTO thermal_units (department_id, name) VALUES
   (1, 'Тепловой узел 1 филиала 1'),
   (1, 'Тепловой узел 2 филиала 1'),
   (1, 'Тепловой узел 3 филиала 1'),
   (2, 'Тепловой узел 1 филиала 2'),
   (2, 'Тепловой узел 2 филиала 2'),
   (2, 'Тепловой узел 3 филиала 2'),
   (3, 'Тепловой узел 1 филиала 3'),
   (3, 'Тепловой узел 2 филиала 3'),
   (3, 'Тепловой узел 3 филиала 3');

CREATE TABLE damages (
    id int PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    thermal_unit_id int REFERENCES thermal_units (id),
    leakage_amount real NOT NULL,
    longitude varchar(32),
    latitude varchar(32),
    date timestamp NOT NULL
);

INSERT INTO damages (thermal_unit_id, leakage_amount, date) VALUES
    (1, 1.55, '2023-12-26 11:55');