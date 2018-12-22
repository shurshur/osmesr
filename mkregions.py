#!/usr/bin/python
# vim:shiftwidth=2:cindent:et
import sys
reload(sys)
sys.setdefaultencoding("utf-8")          # a hack to support UTF-8 
from time import time
import re
from xml.sax import make_parser, handler
import xml
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

def pgconn(_host,_user,_pass,_data):
  if not _data:
    return pgconn(pghost,pguser,pgpass,pgdata)
  conn = "dbname='%s'" % _data
  if _host:
    conn = conn + " host='%s'" % _host
  if _user:
    conn = conn + " user='%s'" % _user
  if _pass:
    conn = conn + " password='%s'" % _pass
  return psycopg2.connect(conn)

pg = pgconn(local_host,local_user,local_pass,local_data)

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
  if iso == "UA-30": iso = "UA-32"
  if iso == "UA-43": iso = "RU-CR"
  if iso == "UA-40": iso = "RU-CR"
  q = "UPDATE esr_data SET iso3166=%s WHERE osm_id=%ld" % (sqlesc(iso), id)
  cc2.execute(q)
print "Elapsed %.3lf seconds" % (time()-t)
print "Fetched %d objects" % i
pg.commit()
print "Commit done"
