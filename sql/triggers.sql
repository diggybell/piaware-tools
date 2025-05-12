/**
    \file triggers.sql
    \brief Defines audit triggers for PiAware Tools database tables
    \ingroup Intel
*/

USE piaware;

DROP TRIGGER IF EXISTS aircraft_insert;
DROP TRIGGER IF EXISTS aircraft_update;
 
DELIMITER $$
CREATE TRIGGER aircraft_insert BEFORE INSERT ON aircraft 
   FOR EACH ROW
   BEGIN
      SET NEW.create_date = NOW();
      SET NEW.modify_date = '0000-00-00 00:00:00';
   END; $$
DELIMITER ;
 
DELIMITER $$
CREATE TRIGGER aircraft_update BEFORE UPDATE ON aircraft
   FOR EACH ROW
   BEGIN
      SET NEW.modify_date = NOW();
   END; $$
DELIMITER ;

DROP TRIGGER IF EXISTS flight_insert;
DROP TRIGGER IF EXISTS flight_update;
 
DELIMITER $$
CREATE TRIGGER flight_insert BEFORE INSERT ON flight 
   FOR EACH ROW
   BEGIN
      SET NEW.create_date = NOW();
      SET NEW.modify_date = '0000-00-00 00:00:00';
   END; $$
DELIMITER ;
 
DELIMITER $$
CREATE TRIGGER flight_update BEFORE UPDATE ON flight
   FOR EACH ROW
   BEGIN
      SET NEW.modify_date = NOW();
   END; $$
DELIMITER ;

DROP TRIGGER IF EXISTS flight_track_insert;
DROP TRIGGER IF EXISTS flight_track_update;
 
DELIMITER $$
CREATE TRIGGER flight_track_insert BEFORE INSERT ON flight_track 
   FOR EACH ROW
   BEGIN
      SET NEW.create_date = NOW();
      SET NEW.modify_date = '0000-00-00 00:00:00';
   END; $$
DELIMITER ;
 
DELIMITER $$
CREATE TRIGGER flight_track_update BEFORE UPDATE ON flight_track
   FOR EACH ROW
   BEGIN
      SET NEW.modify_date = NOW();
   END; $$
DELIMITER ;