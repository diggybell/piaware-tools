/*
    \file faa-classification-data.sql
    \brief Contains standard value crossreference for FAA tables
    \ingroup Intel
*/

USE faa;

DROP TABLE IF EXISTS registrant_type;
CREATE TABLE registrant_type
(
    id_code         VARCHAR(2)          NOT NULL,
    description     VARCHAR(25),

    PRIMARY KEY(id_code)
) ENGINE=InnoDB;

INSERT INTO registrant_type (id_code, description) VALUES
('1', 'Individual'),
('2', 'Partnership'),
('3', 'Corporation'),
('4', 'Co-Owned'),
('5', 'Government'),
('7', 'LLC'),
('8', 'Non Citizen Corporation'),
('9', 'Non Citizen Co-Owned');

DROP FUNCTION IF EXISTS GetRegistrantType;
DELIMITER $$
CREATE FUNCTION GetRegistrantType(search_id VARCHAR(2)) RETURNS VARCHAR(25) DETERMINISTIC
BEGIN
   DECLARE RetStr VARCHAR(25) DEFAULT NULL;

   SELECT description INTO RetStr FROM registrant_type WHERE id_code = search_id;
   RETURN RetStr;
END; $$
DELIMITER ;

DROP TABLE IF EXISTS airworthiness_classification;
CREATE TABLE airworthiness_classification
(
    id_code         VARCHAR(2)          NOT NULL,
    description     VARCHAR(25),

    PRIMARY KEY(id_code)
) ENGINE=InnoDB;

INSERT INTO airworthiness_classification (id_code, description) VALUES
('1', 'Standard'),
('2', 'Limited'),
('3', 'Restricted'),
('4', 'Experimental'),
('5', 'Provisional'),
('6', 'Multiple'),
('7', 'Primary'),
('8', 'Special Flight Permit'),
('9', 'Light Sport');

DROP FUNCTION IF EXISTS GetAirworthinessClassification;
DELIMITER $$
CREATE FUNCTION GetAirworthinessClassification(search_id VARCHAR(2)) RETURNS VARCHAR(25) DETERMINISTIC
BEGIN
   DECLARE RetStr VARCHAR(25) DEFAULT NULL;

   SELECT description INTO RetStr FROM airworthiness_classification WHERE id_code = search_id;
   RETURN RetStr;
END; $$
DELIMITER ;

DROP TABLE IF EXISTS operation_codes;
CREATE TABLE operation_codes
(
    id_code         VARCHAR(2)          NOT NULL,
    description     VARCHAR(25),

    PRIMARY KEY(id_code)
) ENGINE=InnoDB;

INSERT INTO operation_codes (id_code, description) VALUES
('N', 'Normal'),
('U', 'Utility'),
('A', 'Acrobatic'),
('T', 'Transport'),
('G', 'Glider'),
('B', 'Balloon'),
('C', 'Commuter'),
('O', 'Other');

DROP FUNCTION IF EXISTS GetOperationCode;
DELIMITER $$
CREATE FUNCTION GetOperationCode(search_id VARCHAR(2)) RETURNS VARCHAR(25) DETERMINISTIC
BEGIN
   DECLARE RetStr VARCHAR(25) DEFAULT NULL;

   SELECT description INTO RetStr FROM operation_codes WHERE id_code = search_id;
   RETURN RetStr;
END; $$
DELIMITER ;

DROP TABLE IF EXISTS restricted_type;
CREATE TABLE restricted_type
(
    id_code         VARCHAR(2)          NOT NULL,
    description     VARCHAR(25),

    PRIMARY KEY(id_code)
) ENGINE=InnoDB;

INSERT INTO restricted_type (id_code, description) VALUES
('0', 'Other'),
('1', 'Agricultural/Pest Control'),
('2', 'Aerial Surveying'),
('3', 'Aerial Advertising'),
('4', 'Forest'),
('5', 'Patrolling'),
('6', 'Weather Control'),
('7', 'Carriage of Cargo');

DROP FUNCTION IF EXISTS GetRestrictedType;
DELIMITER $$
CREATE FUNCTION GetRestrictedType(search_id VARCHAR(2)) RETURNS VARCHAR(25) DETERMINISTIC
BEGIN
   DECLARE RetStr VARCHAR(25) DEFAULT NULL;

   SELECT description INTO RetStr FROM restricted_type WHERE id_code = search_id;
   RETURN RetStr;
END; $$
DELIMITER ;

DROP TABLE IF EXISTS aircraft_type;
CREATE TABLE aircraft_type
(
    id_code         VARCHAR(2)          NOT NULL,
    description     VARCHAR(25),

    PRIMARY KEY(id_code)
) ENGINE=InnoDB;

INSERT INTO aircraft_type (id_code, description) VALUES
('1', 'Glider'),
('2', 'Balloon'),
('3', 'Blimp/Dirigible'),
('4', 'Fixed/Single'),
('5', 'Fixed/Multi'),
('6', 'Rotorcraft'),
('7', 'Weight Shift'),
('8', 'Powered Parachute'),
('9', 'Gyroplane'),
('H', 'Hybrid Lift'),
('O', 'Other');

DROP FUNCTION IF EXISTS GetAircraftType;
DELIMITER $$
CREATE FUNCTION GetAircraftType(search_id VARCHAR(2)) RETURNS VARCHAR(25) DETERMINISTIC
BEGIN
   DECLARE RetStr VARCHAR(25) DEFAULT NULL;

   SELECT description INTO RetStr FROM aircraft_type WHERE id_code = search_id;
   RETURN RetStr;
END; $$
DELIMITER ;

DROP TABLE IF EXISTS engine_type;
CREATE TABLE engine_type
(
    id_code         VARCHAR(2)          NOT NULL,
    description     VARCHAR(25),

    PRIMARY KEY(id_code)
) ENGINE=InnoDB;

INSERT INTO engine_type (id_code, description) VALUES
('0', 'None'),
('1', 'Reciprocating'),
('2', 'Turbo-prop'),
('3', 'Turbo-shaft'),
('4', 'Turbo-jet'),
('5', 'Turbo-fan'),
('6', 'Ramjet'),
('7', '2 Cycle'),
('8', '4 Cycle'),
('9', 'Unknown'),
('10', 'Electric'),
('11', 'Rotary');

DROP FUNCTION IF EXISTS GetEngineType;
DELIMITER $$
CREATE FUNCTION GetEngineType(search_id VARCHAR(2)) RETURNS VARCHAR(25) DETERMINISTIC
BEGIN
   DECLARE RetStr VARCHAR(25) DEFAULT NULL;

   SELECT description INTO RetStr FROM engine_type WHERE id_code = search_id;
   RETURN RetStr;
END; $$
DELIMITER ;

DROP TABLE IF EXISTS aircraft_category;
CREATE TABLE aircraft_category
(
    id_code         VARCHAR(2)          NOT NULL,
    description     VARCHAR(25),

    PRIMARY KEY(id_code)
) ENGINE=InnoDB;

INSERT INTO aircraft_category (id_code, description) VALUES
('1', 'Land'),
('2', 'Sea'),
('3', 'Amphibian');

DROP FUNCTION IF EXISTS GetAircraftCategory;
DELIMITER $$
CREATE FUNCTION GetAircraftCategory(search_id VARCHAR(2)) RETURNS VARCHAR(25) DETERMINISTIC
BEGIN
   DECLARE RetStr VARCHAR(25) DEFAULT NULL;

   SELECT description INTO RetStr FROM aircraft_category WHERE id_code = search_id;
   RETURN RetStr;
END; $$
DELIMITER ;

DROP TABLE IF EXISTS builder_certification_code;
CREATE TABLE builder_certification_code
(
    id_code         VARCHAR(2)          NOT NULL,
    description     VARCHAR(25),

    PRIMARY KEY(id_code)
) ENGINE=InnoDB;

INSERT INTO builder_certification_code (id_code, description) VALUES
('0', 'Type Certified'),
('1', 'Not Type Certified'),
('2', 'Light Sport');

DROP FUNCTION IF EXISTS GetBuilderCertificationCode;
DELIMITER $$
CREATE FUNCTION GetBuilderCertificationCode(search_id VARCHAR(2)) RETURNS VARCHAR(25) DETERMINISTIC
BEGIN
   DECLARE RetStr VARCHAR(25) DEFAULT NULL;

   SELECT description INTO RetStr FROM builder_certification_code WHERE id_code = search_id;
   RETURN RetStr;
END; $$
DELIMITER ;

DROP TABLE IF EXISTS aircraft_weight_class;
CREATE TABLE aircraft_weight_class
(
    id_code         VARCHAR(2)          NOT NULL,
    description     VARCHAR(25),

    PRIMARY KEY(id_code)
) ENGINE=InnoDB;

INSERT INTO aircraft_weight_class (id_code, description) VALUES
('1', 'Up to 12,499'),
('2', '12,500 - 19,999'),
('3', '20,000 and over'),
('4', 'UAV up to 55');

DROP FUNCTION IF EXISTS GetAircraftWeightClass;
DELIMITER $$
CREATE FUNCTION GetAircraftWeightClass(search_id VARCHAR(2)) RETURNS VARCHAR(25) DETERMINISTIC
BEGIN
   DECLARE RetStr VARCHAR(25) DEFAULT NULL;

   SELECT description INTO RetStr FROM aircraft_weight_class WHERE id_code = search_id;
   RETURN RetStr;
END; $$
DELIMITER ;