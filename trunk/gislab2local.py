#!/usr/bin/python
# vim:shiftwidth=2:cindent:et
import sys
reload(sys)
sys.setdefaultencoding("utf-8")          # a hack to support UTF-8 
from time import time
import re
from xml.sax import make_parser, handler
from xml.utils.iso8601 import parse
import xml
import MySQLdb
import MySQLdb.cursors
import _mysql_exceptions
import psycopg2
from psycopg2.extensions import adapt
from psycopg2.extras import HstoreAdapter, register_hstore
from esr_config import *

try:
  import psyco
  psyco.full()
except ImportError:
  pass

def sqlesc(value):
  adapted = adapt(value)
  if hasattr(adapted, 'getquoted'):
    adapted = adapted.getquoted()
  return adapted

pg = psycopg2.connect("dbname='%s' user='%s' host='%s' password='%s'" % (postgis_data,postgis_user,postgis_host,postgis_pass))
pg2 = psycopg2.connect("dbname='%s' user='%s' host='%s' password='%s'" % (local_data,local_user,local_host,local_pass))

cc = pg.cursor()
register_hstore(cc)
cc2 = pg2.cursor()
register_hstore(cc2)

cc2.execute("DELETE FROM esr_data")

q1 = "SELECT 0,osm_id,ST_AsText(way) AS geom,ST_SRID(way) AS srid,tags,railway,name FROM osm_point WHERE railway IN ('station','halt')"
q2 = "SELECT 1,osm_id,ST_AsText(way) AS geom,ST_SRID(way) AS srid,tags,railway,name FROM osm_line WHERE railway IN ('station','halt')"
q3 = "SELECT 2,osm_id,ST_AsText(way) AS geom,ST_SRID(way) AS srid,tags,railway,name FROM osm_polygon WHERE railway IN ('station','halt')"

q = " UNION ".join([q1,q2,q3])
print "Query: %s" % q

t = time()
print "Run query"
cc.execute(q)
print "Elapsed %.3lf seconds" % (time()-t)
t = time()
print "Fetch data"
i = 0
while True:
  row = cc.fetchone()
  if not row:
    break
  i = i + 1
  type, id, geom, srid, tags, railway, name = row
  tags['railway'] = railway
  tags['name'] = name
  tags = HstoreAdapter(tags)
  tags.prepare(pg2)
  tags = tags.getquoted()
  q = "INSERT INTO esr_data (osm_type, osm_id, tags, geom) VALUES (%d, %d, %s, ST_GeomFromText(%s,%d))" % (type, id, tags, sqlesc(geom), srid)
  cc2.execute(q) 

print "Elapsed %.3lf seconds" % (time()-t)
print "Fetched %d objects" % i
pg2.commit()
print "Commit done"
