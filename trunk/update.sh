#!/bin/sh
#./mkbounds.py
./gislab2local.py
./mkregions.py
./runall.sh
./esr_export.py > esr.csv
./osm2esr_export.py > osm2esr.csv
