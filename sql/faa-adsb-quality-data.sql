/*
    \file faa-adsb-quality-data.sql
    \brief Contains standard value crossreference for FAA ADS-B data quality
    \ingroup Intel
*/

USE faa;

/*

SQL Pattern - Replace table_name and TableName

DROP TABLE IF EXISTS table_name;
CREATE TABLE table_name
(
    id_code         VARCHAR(2)          NOT NULL,
    description     VARCHAR(25),

    PRIMARY KEY(id_code)
) ENGINE=InnoDB;

INSERT INTO table_name (id_code, description) VALUES
('1', '');

DROP FUNCTION IF EXISTS GetTableName;
DELIMITER $$
CREATE FUNCTION GetTableName(search_id VARCHAR(2)) RETURNS VARCHAR(25) DETERMINISTIC
BEGIN
   DECLARE RetStr VARCHAR(25) DEFAULT NULL;

   SELECT description INTO RetStr FROM table_name WHERE id_code = search_id;
   RETURN RetStr;
END; $$
DELIMITER ;

*/

DROP TABLE IF EXISTS adsb_category;
CREATE TABLE adsb_category
(
    id_code         VARCHAR(2)          NOT NULL,
    description     VARCHAR(35),

    PRIMARY KEY(id_code)
) ENGINE=InnoDB;

INSERT INTO adsb_category (id_code, description) VALUES
('A0', 'None'),
('A1', 'Light (< 15500lb)'),
('A2', 'Small (15500-75000lb)'),
('A3', 'Large (75000-300000lb)'),
('A4', 'High Vortex Aircraft'),
('A5', 'Heavy (> 300000lb)'),
('A6', 'High Performance > 5g and 400kt'),
('A7', 'Rotorcraft'),
('B0', 'No Information'),
('B1', 'Glider/Sailplane'),
('B2', 'Airship/Balloon'),
('B3', 'Parachutist/Skydiver'),
('B4', 'Ultralight/Hang-glider/Para-glider'),
('B5', 'Reserved'),
('B6', 'UAV'),
('B7', 'Space Vehicle');

DROP FUNCTION IF EXISTS GetADSBCategory;
DELIMITER $$
CREATE FUNCTION GetADSBCategory(search_id VARCHAR(2)) RETURNS VARCHAR(35) DETERMINISTIC
BEGIN
   DECLARE RetStr VARCHAR(35) DEFAULT NULL;

   SELECT description INTO RetStr FROM adsb_category WHERE id_code = search_id;
   RETURN RetStr;
END; $$
DELIMITER ;

DROP TABLE IF EXISTS nacp_category;
CREATE TABLE nacp_category
(
    id_code         INTEGER          NOT NULL,
    description     VARCHAR(25),

    PRIMARY KEY(id_code)
) ENGINE=InnoDB;

INSERT INTO nacp_category (id_code, description) VALUES
('-1', 'Missing'),
('0', 'Unknown Accuracy'),
('1', 'RNP-10 Accuracy'),
('2', 'RNP-4 Accuracy'),
('3', 'RNP-2 Accuracy'),
('4', 'RNP-1 Accuracy'),
('5', 'RNP-0.5 Accuracy'),
('6', 'RNP-0.3 Accuracy'),
('7', 'RNP-0.1 Accuracy'),
('8', 'GPS (with SA)'),
('9', 'GPS (SA off)'),
('10', 'WAAS'),
('11', 'LAAS'),
('12', 'Reserved'),
('13', 'Reserved'),
('14', 'Reserved'),
('15', 'Reserved');

DROP FUNCTION IF EXISTS GetNACpCategory;
DELIMITER $$
CREATE FUNCTION GetNACpCategory(search_id INTEGER) RETURNS VARCHAR(25) DETERMINISTIC
BEGIN
   DECLARE RetStr VARCHAR(25) DEFAULT NULL;

   SELECT description INTO RetStr FROM nacp_category WHERE id_code = search_id;
   RETURN RetStr;
END; $$
DELIMITER ;

DROP TABLE IF EXISTS nacv_category;
CREATE TABLE nacv_category
(
    id_code         INTEGER          NOT NULL,
    description     VARCHAR(25),

    PRIMARY KEY(id_code)
) ENGINE=InnoDB;

INSERT INTO nacv_category (id_code, description) VALUES
('-1', 'Missing'),
('0', '>= 10m/s'),
('1', '< 10m/s'),
('2', '< 3m/s'),
('3', '< 1m/s'),
('4', '< 0.3m/s');

DROP FUNCTION IF EXISTS GetNACvCategory;
DELIMITER $$
CREATE FUNCTION GetNACvCategory(search_id INTEGER) RETURNS VARCHAR(25) DETERMINISTIC
BEGIN
   DECLARE RetStr VARCHAR(25) DEFAULT NULL;

   SELECT description INTO RetStr FROM nacv_category WHERE id_code = search_id;
   RETURN RetStr;
END; $$
DELIMITER ;

DROP TABLE IF EXISTS nic_category;
CREATE TABLE nic_category
(
    id_code         INTEGER          NOT NULL,
    description     VARCHAR(25),

    PRIMARY KEY(id_code)
) ENGINE=InnoDB;

INSERT INTO nic_category (id_code, description) VALUES
('-1', 'Missing'),
('0', 'Unknown RC'),
('1', 'RC < 20nm'),
('2', 'RC < 8nm'),
('3', 'RC < 4nm'),
('4', 'RC < 2nm'),
('5', 'RC < 1nm'),
('6', 'RC < 0.3-0.6nm'),
('7', 'RC < 0.2nm'),
('8', 'RC < 0.1nm'),
('9', 'RC < 75m'),
('10', 'RC < 25m'),
('11', 'RC < 7.5m'),
('12', 'Reserved'),
('13', 'Reserved'),
('14', 'Reserved'),
('15', 'Reserved');

DROP FUNCTION IF EXISTS GetNICCategory;
DELIMITER $$
CREATE FUNCTION GetNICCategory(search_id INTEGER) RETURNS VARCHAR(25) DETERMINISTIC
BEGIN
   DECLARE RetStr VARCHAR(25) DEFAULT NULL;

   SELECT description INTO RetStr FROM nic_category WHERE id_code = search_id;
   RETURN RetStr;
END; $$
DELIMITER ;

DROP TABLE IF EXISTS nic_baro_category;
CREATE TABLE nic_baro_category
(
    id_code         INTEGER          NOT NULL,
    description     VARCHAR(25),

    PRIMARY KEY(id_code)
) ENGINE=InnoDB;

INSERT INTO nic_baro_category (id_code, description) VALUES
('0', 'Not Cross-checked'),
('1', 'Cross-checked');

DROP FUNCTION IF EXISTS GetNICBaroCategory;
DELIMITER $$
CREATE FUNCTION GetNICBaroCategory(search_id INTEGER) RETURNS VARCHAR(25) DETERMINISTIC
BEGIN
   DECLARE RetStr VARCHAR(25) DEFAULT NULL;

   SELECT description INTO RetStr FROM nic_baro_category WHERE id_code = search_id;
   RETURN RetStr;
END; $$
DELIMITER ;

DROP TABLE IF EXISTS sda_category;
CREATE TABLE sda_category
(
    id_code         INTEGER          NOT NULL,
    description     VARCHAR(25),

    PRIMARY KEY(id_code)
) ENGINE=InnoDB;

INSERT INTO sda_category (id_code, description) VALUES
('-1', 'Missing'),
('0', '> 0.001'),
('1', '<= 0.001'),
('2', '<= 0.00001'),
('3', '<= 0.0000001');

DROP FUNCTION IF EXISTS GetSDACategory;
DELIMITER $$
CREATE FUNCTION GetSDACategory(search_id INTEGER) RETURNS VARCHAR(25) DETERMINISTIC
BEGIN
   DECLARE RetStr VARCHAR(25) DEFAULT NULL;

   SELECT description INTO RetStr FROM sda_category WHERE id_code = search_id;
   RETURN RetStr;
END; $$
DELIMITER ;

DROP TABLE IF EXISTS sil_category;
CREATE TABLE sil_category
(
    id_code         INTEGER          NOT NULL,
    description     VARCHAR(25),

    PRIMARY KEY(id_code)
) ENGINE=InnoDB;

INSERT INTO sil_category (id_code, description) VALUES
('-1', 'Missing'),
('0', '> 0.1'),
('1', '> 0.001'),
('2', '> 0.00001'),
('3', '> 0.0000001');

DROP FUNCTION IF EXISTS GetSILCategory;
DELIMITER $$
CREATE FUNCTION GetSILCategory(search_id INTEGER) RETURNS VARCHAR(25) DETERMINISTIC
BEGIN
   DECLARE RetStr VARCHAR(25) DEFAULT NULL;

   SELECT description INTO RetStr FROM sil_category WHERE id_code = search_id;
   RETURN RetStr;
END; $$
DELIMITER ;

DROP TABLE IF EXISTS gva_category;
CREATE TABLE gva_category
(
    id_code         INTEGER          NOT NULL,
    description     VARCHAR(25),

    PRIMARY KEY(id_code)
) ENGINE=InnoDB;

INSERT INTO gva_category (id_code, description) VALUES
('-1', 'Missing'),
('0', 'Unknown or > 150m'),
('1', '<= 150m'),
('2', '<= 45m'),
('3', 'Reserved');

DROP FUNCTION IF EXISTS GetGVACategory;
DELIMITER $$
CREATE FUNCTION GetGVACategory(search_id INTEGER) RETURNS VARCHAR(25) DETERMINISTIC
BEGIN
   DECLARE RetStr VARCHAR(25) DEFAULT NULL;

   SELECT description INTO RetStr FROM gva_category WHERE id_code = search_id;
   RETURN RetStr;
END; $$
DELIMITER ;
