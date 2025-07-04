/**
    \file schema.sql
    \brief Defines PiAware Tools database tables
    \ingroup Intel
*/

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

    create_date             DATETIME        DEFAULT NULL,
    modify_date             DATETIME        DEFAULT NULL,

    PRIMARY KEY(aircraft_seq)
) ENGINE=InnoDB;

CREATE INDEX icao_ndx
    ON aircraft(icao_hex);
CREATE INDEX tail_ndx
    ON aircraft(n_number);
CREATE INDEX adsb_cat_ndx
    ON aircraft(adsb_category);
CREATE INDEX aircraft_create_ndx
    ON aircraft(create_date);
CREATE INDEX aircraft_modify_ndx
    ON aircraft(modify_date);

CREATE TABLE flight
(
    flight_seq              INTEGER         AUTO_INCREMENT NOT NULL,
    aircraft_seq            INTEGER         NOT NULL,
    flight                  VARCHAR(10),
    first_seen              DATETIME,
    last_seen               DATETIME,
    positions               INTEGER,
    distance                INTEGER,

    create_date             DATETIME        DEFAULT NULL,
    modify_date             DATETIME        DEFAULT NULL,

    PRIMARY KEY(flight_seq),
    FOREIGN KEY(aircraft_seq) REFERENCES aircraft(aircraft_seq)
) ENGINE=InnoDB;

CREATE INDEX aircraft_flight_ndx
    ON flight(aircraft_seq);
CREATE INDEX flight_start_ndx
    ON flight(aircraft_seq, first_seen);
CREATE INDEX flight_number_ndx
    ON flight(flight);
CREATE INDEX flight_create_ndx
    ON flight(create_date);
CREATE INDEX flight_modify_ndx
    ON flight(modify_date);

CREATE TABLE flight_track
(
    track_seq               INTEGER         AUTO_INCREMENT NOT NULL,
    aircraft_seq            INTEGER         NOT NULL,
    flight_seq              INTEGER         DEFAULT NULL,
    time_stamp              DATETIME,
    adsb_category           VARCHAR(2),
    flight                  VARCHAR(10),
    latitude                NUMERIC(9,6),
    longitude               NUMERIC(9,6),
    altitude                INTEGER,
    geo_altitude            INTEGER,
    heading                 NUMERIC(5.1),
    climb_rate              INTEGER,
    transponder             VARCHAR(5),
    qnh                     NUMERIC(6.1),
    groundspeed             NUMERIC(6,1),
    track                   NUMERIC(5,2),
    nic                     SMALLINT,
    rc                      INTEGER,
    nac_p                   SMALLINT,
    nac_v                   SMALLINT,
    sil                     SMALLINT,
    sil_type                VARCHAR(10),
    gva                     SMALLINT,
    sda                     SMALLINT,
    distance                INTEGER,
    bearing                 INTEGER,
    cardinal                VARCHAR(3),
    ring                    INTEGER,
    rssi                    NUMERIC(5,1),

    flight_linked           INTEGER         DEFAULT 0,

    create_date             DATETIME        DEFAULT NULL,
    modify_date             DATETIME        DEFAULT NULL,

    PRIMARY KEY(track_seq),
    FOREIGN KEY(aircraft_seq) REFERENCES aircraft(aircraft_seq),
    FOREIGN KEY(flight_seq) REFERENCES flight(flight_seq)
) ENGINE=InnoDB;

CREATE INDEX aircraft_track_ndx
    ON flight_track(aircraft_seq);
CREATE INDEX flight_track_ndx
    ON flight_track(flight_seq);
CREATE INDEX aircraft_track_sequence_ndx
    ON flight_track(aircraft_seq, time_stamp);
CREATE INDEX track_sequence_ndx
    ON flight_track(time_stamp);
CREATE INDEX flight_time_ndx
    ON flight_track(flight_seq, time_stamp);
CREATE INDEX flight_link_ndx
    ON flight_track(aircraft_seq, flight_linked);
CREATE INDEX flight_track_create_ndx
    ON flight_track(create_date);
CREATE INDEX flight_track_modify_ndx
    ON flight_track(modify_date);
