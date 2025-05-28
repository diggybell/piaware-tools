USE faa;

DROP VIEW IF EXISTS aircraft_details_view;
CREATE VIEW aircraft_details_view AS
SELECT m.n_number,
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
         INNER JOIN faa.acftref a ON (m.mfr_mdl_code = a.code)
         INNER JOIN faa.engine e ON (m.eng_mfr_mdl = e.code);
