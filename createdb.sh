#!/bin/bash

sudo -u postgres psql -c "CREATE DATABASE dump1090 OWNER pi TEMPLATE template1;"

#sudo -u postgres psql -c "CREATE DATABASE dump1090 OWNER pi TEMPLATE template1;"
psql dump1090 < ./db.sql
