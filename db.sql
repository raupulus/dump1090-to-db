DROP TABLE IF EXISTS reports;

CREATE TABLE reports
(
    id              BIGSERIAL PRIMARY KEY,
    icao     VARCHAR(100) NULL , --hex
    category     VARCHAR(100) NULL,

    squawk     VARCHAR(100) NULL,
    flight     VARCHAR(100) NULL,
    lat     FLOAT NULL,
    lon     FLOAT NULL,
    altitude     FLOAT NULL, -- m
    vert_rate     FLOAT NULL, -- m
    track     INTEGER NULL,
    speed     FLOAT NULL, -- mps
    seen_at     TIMESTAMP NULL,
    messages     INTEGER NULL,
    rssi     FLOAT NULL,
    emergency VARCHAR(100) NULL
);
