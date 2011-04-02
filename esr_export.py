#!/usr/bin/python
# vim:encoding=utf-8:shiftwidth=2:cindent
import sys
import MySQLdb
import MySQLdb.cursors
import _mysql_exceptions
from esr_config import *

try:
  import psyco
  psyco.full()
except ImportError:
  pass

db = MySQLdb.connect(osmesr_host,osmesr_user,osmesr_pass,osmesr_data,cursorclass=MySQLdb.cursors.DictCursor)
db.query("SET NAMES utf8")
c = db.cursor()

q = """
SELECT
  esr,
  dup_esr,
  stations.express_code AS express,
  stations.name AS name,
  station_types.name AS type,
  regions.source AS source,
  regions.name AS region,
  countries.esr_name AS country,
  railways.name AS railway,
  divisions.name AS division
FROM
  stations
  LEFT JOIN regions ON stations.region_id = regions.id
  LEFT JOIN railways ON stations.railway_id = railways.id
  LEFT JOIN divisions ON stations.division_id = divisions.id
  LEFT JOIN station_types ON stations.station_type_id = station_types.id
  LEFT JOIN express ON stations.express_code = express.express_code
  LEFT JOIN countries ON stations.country_id = countries.id
ORDER BY
  esr
"""

cols = None
c.execute(q)
while 1:
  row = c.fetchone()
  if not row:
    break
  if not cols:
    cols = row.keys()
    print '"'+'";"'.join(cols)+'"'
  for k in cols:
    if row[k] == None:
      row[k] = ""
  print '"'+'";"'.join(row.values())+'"'

