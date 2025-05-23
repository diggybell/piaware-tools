/**
    \file functions.sql
    \brief Defines PiAware Tools functions
    \ingroup Intel
*/

USE piaware;

DROP FUNCTION IF EXISTS ValidAltitude;
DELIMITER $$
CREATE FUNCTION ValidAltitude(altitude INTEGER, ring INTEGER) RETURNS INTEGER DETERMINISTIC
BEGIN
    DECLARE Ret INTEGER DEFAULT 1;

    CASE ring
        WHEN 0 THEN
            IF (altitude < 100) THEN
                SET Ret = 0;
            END IF;
        WHEN 1 THEN
            IF (altitude < 2000) THEN
                SET Ret = 0;
            END IF;
        WHEN 2 THEN
            IF (altitude < 5000) THEN
                SET Ret = 0;
            END IF;
        WHEN 3 THEN
            IF (altitude < 10000) THEN
                SET Ret = 0;
            END IF;
        WHEN 4 THEN
            IF (altitude < 15000) THEN
                SET Ret = 0;
            END IF;
        WHEN 5 THEN
            IF (altitude < 20000) THEN
                SET Ret = 0;
            END IF;
        ELSE
            SET Ret = 0;
    END CASE;
    RETURN Ret;
END; $$
DELIMITER ;

DROP FUNCTION IF EXISTS GetCardinal;
DELIMITER $$
CREATE FUNCTION GetCardinal(bearing INTEGER) RETURNS VARCHAR(3) DETERMINISTIC
BEGIN
    DECLARE Ret VARCHAR(3) DEFAULT NULL;

    SET @index = FLOOR(MOD(((bearing) / (360 / 16)), 16));

    CASE @index
        WHEN  0 THEN SET Ret = 'N';
        WHEN  1 THEN SET Ret = 'NNE';
        WHEN  2 THEN SET Ret = 'NE';
        WHEN  3 THEN SET Ret = 'ENE';
        WHEN  4 THEN SET Ret = 'E';
        WHEN  5 THEN SET Ret = 'ESE';
        WHEN  6 THEN SET Ret = 'SE';
        WHEN  7 THEN SET Ret = 'SSE';
        WHEN  8 THEN SET Ret = 'S';
        WHEN  9 THEN SET Ret = 'SSW';
        WHEN 10 THEN SET Ret = 'SW';
        WHEN 11 THEN SET Ret = 'WSW';
        WHEN 12 THEN SET Ret = 'W';
        WHEN 13 THEN SET Ret = 'WNW';
        WHEN 14 THEN SET Ret = 'NW';
        WHEN 15 THEN SET Ret = 'NNW';
        ELSE SET Ret = 'INV';
    END CASE;

    RETURN Ret;
END; $$
DELIMITER ;

DROP FUNCTION IF EXISTS GetRangeRing;
DELIMITER $$
CREATE FUNCTION GetRangeRing(distance INTEGER) RETURNS INTEGER DETERMINISTIC
BEGIN
    DECLARE Ret INTEGER DEFAULT -1;

    SET Ret = distance DIV 50;

    IF (Ret > 5) THEN
        SET Ret = 5;
    END IF;

    IF (Ret * 50 = distance) AND (Ret != 0) THEN
        SET Ret = Ret - 1;
    END IF;
    RETURN Ret;
END; $$
DELIMITER ;

DROP FUNCTION IF EXISTS CompleteQAData;
DELIMITER $$
CREATE FUNCTION CompleteQAData(nic INTEGER, rc INTEGER, nac_p INTEGER, nac_v INTEGER, sil INTEGER, gva INTEGER, sda INTEGER) RETURNS INTEGER DETERMINISTIC
BEGIN
    DECLARE Ret INTEGER DEFAULT 1;

    IF (nic = -1) OR
       (rc = -1) OR
       (nac_p = -1) OR
       (nac_v = -1) OR
       (sil = -1) OR
       (gva = -1) OR
       (sda = -1) THEN
       SET Ret = 0;
    END IF;

    RETURN Ret;
END; $$
DELIMITER ;
