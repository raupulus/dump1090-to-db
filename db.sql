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
    icao     VARCHAR(100), --hex
    category     VARCHAR(100),
    squawk     VARCHAR(100),
    flight     VARCHAR(100),
    lat     VARCHAR(100),
    lon     VARCHAR(100),
    altitude     VARCHAR(100),
    vert_rate     VARCHAR(100),
    track     VARCHAR(100),
    speed     VARCHAR(100),
    seen_at     VARCHAR(100),
    rssi     VARCHAR(100),
    emergency VARCHAR(100)
);
