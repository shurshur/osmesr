#!/usr/bin/python
# vim:encoding=utf-8:shiftwidth=2:cindent:et
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
  type,
  osm_id,
  status,
  lat,
  lon,
  name,
  alt_name,
  old_name,
  official_name,
  railway,
  user
FROM
  osm2esr,
  osmdata
WHERE
  osmdata_id = osmdata.id
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
    #cols = row.keys()
    cols = "esr:status:type:osm_id:lat:lon:name:alt_name:old_name:official_name:railway:user".split(":")
    print '"'+'";"'.join(cols)+'"'
  out = []
  for k in cols:
    if row[k] == None:
      row[k] = ""
    else:
      row[k] = str(row[k])
    out.append(row[k])
  print '"'+'";"'.join(out)+'"'

