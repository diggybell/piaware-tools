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

DROP TABLE IF EXISTS airworthiness_certification;
CREATE TABLE airworthiness_certification
(
    id_code         VARCHAR(2)          NOT NULL,
    description     VARCHAR(25),

    PRIMARY KEY(id_code)
) ENGINE=InnoDB;

INSERT INTO airworthiness_certification (id_code, description) VALUES
('1', 'Standard'),
('2', 'Limited'),
('3', 'Restricted'),
('4', 'Experimental'),
('5', 'Provisional'),
('6', 'Multiple'),
('7', 'Primary'),
('8', 'Special Flight Permit'),
('9', 'Light Sport');

DROP FUNCTION IF EXISTS GetAirworthinessCertification;
DELIMITER $$
CREATE FUNCTION GetAirworthinessCertification(search_id VARCHAR(10)) RETURNS VARCHAR(25) DETERMINISTIC
BEGIN
   DECLARE RetStr VARCHAR(25) DEFAULT NULL;
   DECLARE FirstChar CHAR DEFAULT SUBSTRING(search_id, 1, 1);

   SELECT description INTO RetStr FROM airworthiness_certification WHERE id_code = FirstChar;
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

DROP FUNCTION IF EXISTS GetOperationDescription;
DELIMITER $$
CREATE FUNCTION GetOperationDescription(certification VARCHAR(10)) RETURNS VARCHAR(200) DETERMINISTIC
BEGIN
    DECLARE RetStr VARCHAR(200) DEFAULT '';
    DECLARE classification VARCHAR(1) DEFAULT SUBSTRING(certification, 1, 1);
    DECLARE airworthiness VARCHAR(1) DEFAULT '';
    DECLARE search_string VARCHAR(2) DEFAULT '';

    SET certification = SUBSTRING(certification, 2);
    IF (classification = '1') THEN
        WHILE (LENGTH(certification) > 0) DO
            IF (LENGTH(RetStr) > 0) THEN
                SET RetStr = CONCAT(RetStr,'/');
            END IF;
            SET RetStr = CONCAT(RetStr, GetOperationCode(SUBSTRING(certification, 1, 1)));
            SET certification = SUBSTRING(certification, 2);
        END WHILE;
    ELSEIF (classification = '2') THEN
        SET RetStr = 'None';
    ELSEIF (classification = '3') THEN
        WHILE (LENGTH(certification) > 0) DO
            IF (LENGTH(RetStr) > 0) THEN
                SET RetStr = CONCAT(RetStr,'/');
            END IF;
            SET RetStr = CONCAT(RetStr, GetRestrictedType(SUBSTRING(certification, 1, 1)));
            SET certification = SUBSTRING(certification, 2);
        END WHILE;
    ELSEIF (classification = '4') THEN
        WHILE (LENGTH(certification) > 0) DO
            IF (LENGTH(RetStr) > 0) THEN
                SET RetStr = CONCAT(RetStr,'/');
            END IF;
            IF (SUBSTRING(certification, 1, 1) = '8' OR SUBSTRING(certification, 1, 1) = '9') THEN
                SET search_string = SUBSTRING(certification, 1, 2);
                SET certification = SUBSTRING(certification, 3);
            ELSE
                SET search_string = SUBSTRING(certification, 1, 1);
                SET certification = SUBSTRING(certification, 2);
            END IF;
            SET RetStr = CONCAT(RetStr, GetExperimentalType(search_string));
        END WHILE;
    ELSEIF (classification = '5') THEN
        WHILE (LENGTH(certification) > 0) DO
            IF (LENGTH(RetStr) > 0) THEN
                SET RetStr = CONCAT(RetStr,'/');
            END IF;
            SET RetStr = CONCAT(RetStr, GetProvisionalType(SUBSTRING(certification, 1, 1)));
            SET certification = SUBSTRING(certification, 2);
        END WHILE;
    ELSEIF (classification = '6') THEN
        SET airworthiness = SUBSTRING(certification, 1, 1);
        SET certification = SUBSTRING(certification, 2);
        SET RetStr = GetAirworthinessCertification(airworthiness);
        WHILE (LENGTH(certification) > 0) DO
            IF (LENGTH(RetStr) > 0) THEN
                SET RetStr = CONCAT(RetStr,'/');
            END IF;
            SET RetStr = CONCAT(RetStr, GetRestrictedType(SUBSTRING(certification, 1, 1)));
            SET certification = SUBSTRING(certification, 2);
        END WHILE;
    ELSEIF (classification = '7') THEN
        WHILE (LENGTH(certification) > 0) DO
            IF (LENGTH(RetStr) > 0) THEN
                SET RetStr = CONCAT(RetStr,'/');
            END IF;
            SET RetStr = CONCAT(RetStr, GetProvisionalType(SUBSTRING(certification, 1, 1)));
            SET certification = SUBSTRING(certification, 2);
        END WHILE;
    ELSEIF (classification = '8') THEN
        WHILE (LENGTH(certification) > 0) DO
            IF (LENGTH(RetStr) > 0) THEN
                SET RetStr = CONCAT(RetStr,'/');
            END IF;
            SET RetStr = CONCAT(RetStr, GetSpecialPermitType(SUBSTRING(certification, 1, 1)));
            SET certification = SUBSTRING(certification, 2);
        END WHILE;

    ELSEIF (classification = '9') THEN
        WHILE (LENGTH(certification) > 0) DO
            IF (LENGTH(RetStr) > 0) THEN
                SET RetStr = CONCAT(RetStr,'/');
            END IF;
            SET RetStr = CONCAT(RetStr, GetLightSportType(SUBSTRING(certification, 1, 1)));
            SET certification = SUBSTRING(certification, 2);
        END WHILE;
    END IF;
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

DROP TABLE IF EXISTS experimental_type;
CREATE TABLE experimental_type
(
    id_code         VARCHAR(2)          NOT NULL,
    description     VARCHAR(35),

    PRIMARY KEY(id_code)
) ENGINE=InnoDB;

INSERT INTO experimental_type (id_code, description) VALUES
('0', 'Compliance With FAR'),
('1', 'Research and Development'),
('2', 'Amateur Built'),
('3', 'Exhibition'),
('4', 'Racing'),
('5', 'Crew Training'),
('6', 'Market Survey'),
('7', 'Operating Kit Built Aircraft'),
('8A', 'Reg Prior to 01/31/08'),
('8B', 'Operationg LSA Kit Built'),
('8C', 'Operating LSA Under 21.190'),
('9A', 'Unmanned - Research and Development'),
('9B', 'Unmanned - Market Survey'),
('9C', 'Unmanned - Crew Training'),
('9D', 'Unmanned - Exhibition'),
('9E', 'Unmanned - Compliance with CFR');

DROP FUNCTION IF EXISTS GetExperimentalType;
DELIMITER $$
CREATE FUNCTION GetExperimentalType(search_id VARCHAR(2)) RETURNS VARCHAR(40) DETERMINISTIC
BEGIN
   DECLARE RetStr VARCHAR(40) DEFAULT NULL;

   SELECT description INTO RetStr FROM experimental_type WHERE id_code = search_id;
   RETURN RetStr;
END; $$
DELIMITER ;

DROP TABLE IF EXISTS provisional_type;
CREATE TABLE provisional_type
(
    id_code         VARCHAR(2)          NOT NULL,
    description     VARCHAR(25),

    PRIMARY KEY(id_code)
) ENGINE=InnoDB;

INSERT INTO provisional_type (id_code, description) VALUES
('1', 'Class I'),
('4', 'Class II');

DROP FUNCTION IF EXISTS GetProvisionalType;
DELIMITER $$
CREATE FUNCTION GetProvisionalType(search_id VARCHAR(10)) RETURNS VARCHAR(25) DETERMINISTIC
BEGIN
   DECLARE RetStr VARCHAR(25) DEFAULT NULL;

   SELECT description INTO RetStr FROM provisional_type WHERE id_code = search_id;
   RETURN RetStr;
END; $$
DELIMITER ;

DROP TABLE IF EXISTS special_permit_type;
CREATE TABLE special_permit_type
(
    id_code         VARCHAR(2)          NOT NULL,
    description     VARCHAR(35),

    PRIMARY KEY(id_code)
) ENGINE=InnoDB;

INSERT INTO special_permit_type (id_code, description) VALUES
('1', 'Ferry Flight'),
('2', 'Evacuation Danger'),
('3', 'Excess of Maximum Certified'),
('4', 'Delivery or Export'),
('5', 'Production Flight Test'),
('6', 'Customer Demo');

DROP FUNCTION IF EXISTS GetSpecialPermitType;
DELIMITER $$
CREATE FUNCTION GetSpecialPermitType(search_id VARCHAR(10)) RETURNS VARCHAR(35) DETERMINISTIC
BEGIN
   DECLARE RetStr VARCHAR(35) DEFAULT NULL;

   SELECT description INTO RetStr FROM special_permit_type WHERE id_code = search_id;
   RETURN RetStr;
END; $$
DELIMITER ;

DROP TABLE IF EXISTS light_sport_type;
CREATE TABLE light_sport_type
(
    id_code         VARCHAR(2)          NOT NULL,
    description     VARCHAR(25),

    PRIMARY KEY(id_code)
) ENGINE=InnoDB;

INSERT INTO light_sport_type (id_code, description) VALUES
('A', 'Airplane'),
('G', 'Glider'),
('L', 'Lighter Than Air'),
('P', 'Power Parachute'),
('W', 'Weight Shift');

DROP FUNCTION IF EXISTS GetLightSportType;
DELIMITER $$
CREATE FUNCTION GetLightSportType(search_id VARCHAR(10)) RETURNS VARCHAR(25) DETERMINISTIC
BEGIN
   DECLARE RetStr VARCHAR(25) DEFAULT NULL;

   SELECT description INTO RetStr FROM light_sport_type WHERE id_code = search_id;
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
CREATE FUNCTION GetAircraftWeightClass(search_id VARCHAR(10)) RETURNS VARCHAR(25) DETERMINISTIC
BEGIN
   DECLARE RetStr VARCHAR(25) DEFAULT NULL;

   SET search_id = REPLACE(search_id, 'CLASS ', '');

   SELECT description INTO RetStr FROM aircraft_weight_class WHERE id_code = search_id;
   RETURN RetStr;
END; $$
DELIMITER ;