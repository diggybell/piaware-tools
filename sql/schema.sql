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

CREATE TABLE flight
(
    flight_seq              INTEGER         AUTO_INCREMENT NOT NULL,
    aircraft_seq            INTEGER         NOT NULL,
    first_seen              DATETIME,
    last_seen               DATETIME,

    create_date             DATETIME        DEFAULT NULL,
    modify_date             DATETIME        DEFAULT NULL,

    PRIMARY KEY(flight_seq),
    FOREIGN KEY(aircraft_seq) REFERENCES aircraft(aircraft_seq)
) ENGINE=InnoDB;

CREATE INDEX aircraft_flight_ndx
    ON flight(aircraft_seq);
CREATE INDEX flight_start_ndx
    ON flight(aircraft_seq, first_seen);

CREATE TABLE flight_track
(
    track_seq               INTEGER         AUTO_INCREMENT NOT NULL,
    flight_seq              INTEGER         NOT NULL,
    time_stamp              DATETIME,
    latitude                NUMERIC(9,6),
    longitude               NUMERIC(9,6),
    altitude                INTEGER,
    groundspeed             NUMERIC(6,1),
    track                   NUMERIC(5,2),
    distance                INTEGER,
    bearing                 INTEGER,
    cardinal                VARCHAR(3),
    ring                    INTEGER,
    rssi                    NUMERIC(5,1),

    create_date             DATETIME        DEFAULT NULL,
    modify_date             DATETIME        DEFAULT NULL,

    PRIMARY KEY(track_seq),
    FOREIGN KEY(flight_seq) REFERENCES flight(flight_seq)
) ENGINE=InnoDB;

CREATE INDEX flight_track_ndx
    ON flight_track(flight_seq);
CREATE INDEX flight_time_ndx
    ON flight_track(flight_seq, time_stamp);