DROP DATABASE IF EXISTS airline;
CREATE DATABASE airline;
USE airline;

CREATE TABLE airline
(
    code            VARCHAR(3)      NOT NULL,
    name            VARCHAR(60),
    type            INTEGER,

    PRIMARY KEY(code)
) ENGINE=InnoDB;

CREATE TABLE airline_type
(
    code            INTEGER         NOT NULL AUTO_INCREMENT,
    description     VARCHAR(25),

    PRIMARY KEY(code)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS airline_dba;
CREATE TABLE airline_dba
(
    name            VARCHAR(60)     NOT NULL,
    code            VARCHAR(3)      NOT NULL,

    PRIMARY KEY(name)
) ENGINE=InnoDB;
