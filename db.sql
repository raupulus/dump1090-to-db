/*
DROP TABLE IF EXISTS daily_report;

CREATE TABLE daily_report
(
    id              BIGSERIAL PRIMARY KEY,
    report_date     VARCHAR(100),
    transponder     VARCHAR(100),
    messages        VARCHAR(100),
    flight          VARCHAR(100),
    category        VARCHAR(100),
    squawk          VARCHAR(100),
    first_seen      VARCHAR(100),
    first_latitude  VARCHAR(100),
    first_longitude VARCHAR(100),
    first_altitude  VARCHAR(100),
    last_seen       VARCHAR(100),
    last_latitude   VARCHAR(100),
    last_longitude  VARCHAR(100),
    last_altitude   VARCHAR(100),
    low_dist        VARCHAR(100),
    high_dist       VARCHAR(100),
    low_rssi        VARCHAR(100),
    high_rssi       VARCHAR(100),
    mlat            VARCHAR(100)
);
 */

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
    altitude     FLOAT NULL,
    vert_rate     FLOAT NULL,
    track     INTEGER NULL,
    speed     FLOAT NULL,
    seen_at     TIMESTAMP NULL,
    messages     INTEGER NULL,
    rssi     FLOAT NULL,
    emergency VARCHAR(100) NULL
);
