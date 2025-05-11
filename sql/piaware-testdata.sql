USE piaware;

INSERT INTO aircraft(icao_hex, n_number, adsb_category) VALUES ('A00001', 'N1', 'A1');
INSERT INTO flight (aircraft_seq, first_seen, last_seen) VALUES (1, '2025-05-10 08:00:00', '2025-05-10 09:00:00');
INSERT INTO flight_track (flight_seq, time_stamp, latitude, longitude) VALUES (1, '2025-05-10 08:00:00', 33.100, -99.00);
INSERT INTO flight_track (flight_seq, time_stamp, latitude, longitude) VALUES (1, '2025-05-10 08:00:30', 33.200, -99.400);