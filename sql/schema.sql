DROP DATABASE IF EXISTS piaware;
CREATE DATABASE piaware;
USE piaware;

CREATE TABLE aircraft
(
    aircraft_seq            INTEGER         AUTO_INCREMENT NOT NULL,
    icao_hex                VARCHAR(6)      NOT NULL,
    n_number                VARCHAR(6)      DEFAULT '',
    adsb_category           VARCHAR(2),
    register_country        VARCHAR(30),

    PRIMARY KEY(aircraft_seq)
) ENGINE=InnoDB;

CREATE INDEX icao_ndx
    ON aircraft(icao_hex);
CREATE INDEX tail_ndx
    ON aircraft(n_number);