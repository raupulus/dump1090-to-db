DROP TABLE IF EXISTS reports;

CREATE TABLE reports
(
    id              BIGSERIAL PRIMARY KEY,
    icao            VARCHAR(100) NULL, -- hex
    category        VARCHAR(100) NULL,
    squawk          VARCHAR(100) NULL,
    flight          VARCHAR(100) NULL,
    lat             FLOAT NULL,
    lon             FLOAT NULL,
    altitude        FLOAT NULL, -- m
    vert_rate       FLOAT NULL, -- m/s
    track           FLOAT NULL,
    speed           FLOAT NULL, -- m/s
    seen_at         TIMESTAMP NULL,
    messages        INTEGER NULL,
    rssi            FLOAT NULL,
    emergency       VARCHAR(100) NULL,
    registration    VARCHAR(100) NULL,
    aircraft_type   VARCHAR(100) NULL,
    wtc             VARCHAR(10) NULL,
    aircraft_desc   VARCHAR(10) NULL,
    nav_altitude_mcp FLOAT NULL, -- m
    nav_qnh         FLOAT NULL, -- hPa
    nav_heading     FLOAT NULL, -- deg
    mach            FLOAT NULL,
    mag_heading     FLOAT NULL, -- deg
    roll            FLOAT NULL, -- deg
    ias             FLOAT NULL, -- m/s
    tas             FLOAT NULL, -- m/s
    geom_rate       FLOAT NULL, -- m/s
    nic             INTEGER NULL,
    rc              FLOAT NULL -- m
);

CREATE TABLE IF NOT EXISTS aircraft_state
(
    icao         VARCHAR(100) PRIMARY KEY,
    messages     INTEGER NOT NULL,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
