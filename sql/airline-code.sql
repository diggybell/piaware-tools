SELECT DISTINCT
    SUBSTR(pf.flight, 1, 3) AS code,
    pf.flight,
    dv.name,
    aa.name
FROM
    piaware.aircraft pa
        INNER JOIN piaware.flight pf ON (pa.aircraft_seq = pf.aircraft_seq)
        LEFT JOIN faa.aircraft_details_view dv ON (pa.icao_hex = dv.icao_hex)
        LEFT JOIN airline.airline aa ON (SUBSTR(pf.flight,1,3) = aa.code)
WHERE
    pa.icao_hex BETWEEN 'A00000' AND 'ADF7C7' AND
    pa.n_number != pf.flight AND
    dv.operations IN ('Transport','Commuter') AND
    aa.name IS NULL
ORDER BY
    pf.flight
;
