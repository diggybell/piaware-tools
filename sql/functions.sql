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
    END CASE;
    RETURN Ret;
END; $$
DELIMITER ;
