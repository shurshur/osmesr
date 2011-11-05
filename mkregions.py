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

pg = psycopg2.connect("dbname='%s' user='%s' host='%s' password='%s'" % (local_data,local_user,local_host,local_pass))

cc = pg.cursor()
register_hstore(cc)
cc2 = pg.cursor()
register_hstore(cc2)

q = "SELECT a.osm_id,a.tags,b.iso3166 FROM esr_data a,esr_bounds b WHERE ST_Intersects(a.geom,b.way) AND b.iso3166!=''"
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
  id, tags, iso = row
  if iso == "RU-MOW": iso = "RU-MOS"
  if iso == "RU-SPE": iso = "RU-LEN"
  q = "UPDATE esr_data SET iso3166=%s WHERE osm_id=%ld" % (sqlesc(iso), id)
  cc2.execute(q)
print "Elapsed %.3lf seconds" % (time()-t)
print "Fetched %d objects" % i
pg.commit()
print "Commit done"
