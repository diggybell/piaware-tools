USE faa;

DROP VIEW IF EXISTS aircraft_details_view;
CREATE VIEW aircraft_details_view AS
SELECT CONCAT('N', m.n_number) AS n_number,
       m.mode_s_code_hex AS icao_hex,
       m.name,
       faa.GetRegistrantType(type_registrant) AS registrant_type,
--       m.street,
--       m.street2,
       m.city,
       m.state,
--       m.zip_code,
       m.air_worth_date,
       faa.GetAirworthinessCertification(m.certification) AS airworthiness,
       faa.GetOperationDescription(m.certification) AS operations,
       m.year_mfr AS manufacture_year,
       m.type_registrant,
       a.mfr AS aircraft_manufacturer,
       a.model AS aircraft_model,
       faa.GetAircraftType(a.type_acft) AS aircraft_type,
       faa.GetAircraftCategory(a.ac_cat) AS aircraft_category,
       a.no_eng,
       faa.GetAircraftWeightClass(a.ac_weight) AS weight_class,
       e.mfr AS engine_manufacturer,
       e.model AS engine_model,
       faa.GetEngineType(e.type) AS engine_type
  FROM faa.master m
         LEFT JOIN faa.acftref a ON (m.mfr_mdl_code = a.code)
         LEFT JOIN faa.engine e ON (m.eng_mfr_mdl = e.code);

DROP VIEW IF EXISTS us_aircraft_view;
CREATE VIEW us_aircraft_view AS
SELECT
	pa.icao_hex AS icao_hex,
    pa.adsb_category AS adsb_category,
    pa.register_country AS register_country,
--    pf.flight AS flight,
--    pf.first_seen AS first_seen,
--    pf.last_seen AS last_seen,
    COALESCE(dm.n_number, pa.n_number) AS n_number,
    COALESCE(dm.type_registrant, fm.type_registrant) AS type_registrant,
    COALESCE(dm.certification, fm.certification) AS certification,
    COALESCE(dm.name,fm.name) AS name,
    COALESCE(dm.street,fm.street) AS street,
    COALESCE(dm.city,fm.city) AS city,
    COALESCE(dm.state,fm.state) AS state,
    COALESCE(dm.branch,'n/a') AS branch,
    COALESCE(dm.base,'n/a') AS base,
    COALESCE(dm.unit_notes, 'n/a') AS unit_notes,
    COALESCE(da.mfr,fa.mfr) AS aircraft_manufacturer,
    COALESCE(da.model,fa.model) AS aircraft_model,
    COALESCE(da.type_acft,fa.type_acft) AS aircraft_type,
    COALESCE(de.mfr,fe.mfr) AS engine_manufacturer,
    COALESCE(de.model,fe.model) AS engine_model,
    COALESCE(de.type,fe.type) AS engine_type,
    pa.aircraft_seq AS aircraft_seq,
    (SELECT MAX(pf.flight_seq) FROM piaware.flight pf WHERE pf.aircraft_seq = pa.aircraft_seq) AS last_flight_seq,
    (SELECT MAX(pf.first_seen) FROM piaware.flight pf WHERE pf.aircraft_seq = pa.aircraft_seq) AS last_flight_date
FROM
	piaware.aircraft pa
--		INNER JOIN piaware.flight pf ON (pa.aircraft_seq = pf.aircraft_seq)
        LEFT JOIN dod.master dm ON (pa.icao_hex = dm.mode_s_code_hex)
        LEFT JOIN dod.acftref da ON (dm.mfr_mdl_code = da.code)
        LEFT JOIN dod.engine de ON (dm.eng_mfr_mdl = de.code)
        LEFT JOIN faa.master fm ON (pa.icao_hex = fm.mode_s_code_hex)
        LEFT JOIN faa.acftref fa ON (fm.mfr_mdl_code = fa.code)
        LEFT JOIN faa.engine fe ON (fm.eng_mfr_mdl = fe.code)
WHERE
	pa.icao_hex BETWEEN 'A00000' AND 'AFFFFF'
ORDER BY
	icao_hex
;

