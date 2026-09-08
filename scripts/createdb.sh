#!/bin/bash

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

sudo -u postgres psql -c "CREATE DATABASE dump1090 OWNER pi TEMPLATE template1;"
psql dump1090 < "${PROJECT_DIR}/db.sql"
