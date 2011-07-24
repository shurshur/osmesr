#!/usr/bin/python
# vim:encoding=utf-8:shiftwidth=2:cindent:et
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
from esr_config import *

try:
  import psyco
  psyco.full()
except ImportError:
  pass

typeof = type

def cname(name):
  # convert to canonical name
  name = re.sub(r'-',' ',name)
  name = re.sub(r'\s+',' ',name)
  name = re.sub(r'Ё','Е',name.encode('utf-8')).decode('utf-8')
  name = re.sub(r'ё','е',name.encode('utf-8')).decode('utf-8')
  name = re.sub(r'IX','9',name.encode('utf-8')).decode('utf-8')
  name = re.sub(r'VIII','8',name.encode('utf-8')).decode('utf-8')
  name = re.sub(r'VII','7',name.encode('utf-8')).decode('utf-8')
  name = re.sub(r'VI','6',name.encode('utf-8')).decode('utf-8')
  name = re.sub(r'IV','4',name.encode('utf-8')).decode('utf-8')
  name = re.sub(r'V','5',name.encode('utf-8')).decode('utf-8')
  name = re.sub(r'III','3',name.encode('utf-8')).decode('utf-8')
  name = re.sub(r'II','2',name.encode('utf-8')).decode('utf-8')
  name = re.sub(r'I','1',name.encode('utf-8')).decode('utf-8')
  return name.upper()

def cname_esr(name):
  return cname(name)

def cname_osm(name):
  return cname(name)

# ===
#  Stage 1. Initializing
# ===

try:
  source = sys.argv[1]
  input = sys.argv[2]
except:
  print """
ESR parser for OSM (C) Sergey Gladilin, Alexandr Zeinalov, 2010

Usage: ./esr_parser.py SOURCE INPUT

Where:
  SOURCE - source name of region
  INPUT - file name or - for stdin or @ for PostGIS
"""
  sys.exit(1)

print "Run esr parser for source='%s' from input='%s'" % (source, input)

if input == "@":
  pg=psycopg2.connect("dbname='%s' user='%s' host='%s' password='%s'" % (postgis_data,postgis_user,postgis_host,postgis_pass))

db=MySQLdb.connect(osmesr_host,osmesr_user,osmesr_pass,osmesr_data,cursorclass=MySQLdb.cursors.DictCursor)
db.query("SET NAMES utf8")

osmdata = dict()

source = sys.argv[1]
c = db.cursor()
c.execute("SELECT COUNT(*) AS c FROM regions WHERE source='%s'" % source)
rs=int(c.fetchone()['c'])
if rs<1:
  print "ERROR! '%s' not found in regions" % source
  sys.exit(1)

c.execute("SELECT * FROM osmdata WHERE source='%s'" % source)
while 1:
  row = c.fetchone()
  if not row:
    break
  k = "%s-%s" % (row["type"], row["osm_id"])
  row["st"] = -1
  row["osm_id"] = str(row["osm_id"])
  row["type"] = str(row["type"])
  osmdata[k] = row
print "OSMDATA: %d records loaded" % len(osmdata)

# ===
#  Stage 2. Parse OSM from OSM XML or from PostGIS
# ===

class osmParser(handler.ContentHandler):
  def __init__(self, filename, source):
    # write here
    self.source = source
    try:
      parser = make_parser()
      parser.setContentHandler(self)
      parser.parse(filename)
    except xml.sax._exceptions.SAXParseException:
      sys.stderr.write( "Error loading %s\n" % filename)

  def startElement(self, name, attrs):
    if name in ['node','way','relation']:
      self.type = str({'node':0,'way':1,'relation':2}[name])
      self.vtype = name
      self.osm_id = str(attrs.get('id'))
      if name == "node":
        self.lat = str(attrs.get('lat'))
        self.lon = str(attrs.get('lon'))
      else:
        self.lat = "0";
        self.lon = "0";
      self.tags = dict()
    if name == 'tag':
      self.tags[attrs.get('k')] = str(attrs.get('v'))

  def endElement(self, _name):
    if _name in ['node','way','relation']:
      if not (('railway' in self.tags.keys()) and (self.tags['railway'] in ['station','halt'])):
        return
    else:
      return
    # see http://wiki.openstreetmap.org/wiki/Proposed_features/Transport_modes
    if 'transport' in self.tags.keys() and self.tags['transport'] not in ['rail','train']:
      return
    # http://wiki.openstreetmap.org/w/index.php?title=Relations/Proposed/Station
    # currently: light_rail (4), monorail (6), subway (231)
    if 'station' in self.tags.keys() and self.tags['station']!='':
      return
    row = dict()
    row["type"] = str(self.type)
    row["osm_id"] = str(self.osm_id)
    row["source"] = str(self.source)
    row["railway"] = self.tags['railway']
    row["lat"] = self.lat;
    row["lon"] = self.lon;
    for t in ['name','alt_name','old_name','official_name']:
      if t+":ru" in self.tags.keys():
        row[t] = self.tags[t+":ru"]
      elif t in self.tags.keys():
        row[t] = self.tags[t]
      else:
        row[t] = ''
    if "esr:user" in self.tags.keys():
      row["user"] = str(self.tags['esr:user'])
    else:
      row['user'] = ''
    print "%s %s: %s %s" % (self.vtype,self.osm_id,row['railway'],row["name"])
    k = "%s-%s" % (row["type"],row["osm_id"])
    # case 1: record found
    if k in osmdata.keys():
      dirty = 0
      for kk in row.keys():
        if kk == 'st' or kk == 'id':
          continue
        if str(osmdata[k][kk]) != str(row[kk]):
          osmdata[k][kk] = row[kk]
          dirty = 1
          print "FIXME ...... %s [%s] | [%s]" % (kk,row[kk],osmdata[k][kk])
          print "FIXME types: " + str(typeof(row[kk])) + " " + str(typeof(osmdata[k][kk]))
      osmdata[k]['st'] = dirty
    # case 2: record not found, add new
    else:
      row['st'] = 2
      osmdata[k] = row

if input == "-":
  a = osmParser(sys.stdin, source)
elif input == "@":
  cc = pg.cursor()
  typs = ["point","line","poly"]
  typi = [0,1,1]
  for typ in [0,1,2]:
    # note: station must be NULL!!!
    if typ == 0:
      cc.execute("SELECT osm_id,lat/10000000.,lon/10000000.,name,name_ru,alt_name,alt_name_ru,railway,transport,esr_user,station,old_name,official_name FROM %s_%s_attr,osm_nodes WHERE railway IN ('station','halt') AND (transport IN ('rail','train') OR transport IS NULL) AND station IS NULL AND id=osm_id" % (source,typs[typ]))
    else:
      cc.execute("SELECT osm_id,0,0,name,name_ru,alt_name,alt_name_ru,railway,transport,esr_user,station,old_name,official_name FROM %s_%s_attr WHERE railway IN ('station','halt') AND (transport IN ('rail','train') OR transport IS NULL) AND station IS NULL" % (source,typs[typ]))
    while 1:
      row = cc.fetchone()
      if not row:
        break
      osm_id,lat,lon,name,name_ru,alt_name,alt_name_ru,railway,transport,esr_user,station,old_name,official_name = row
      row = {}
      row['osm_id'] = osm_id
      row['lat'] = str(lat)
      row['lon'] = str(lon)
      row['name'] = name
      if name_ru and name_ru!="":
        row['name'] = name_ru
      row['alt_name'] = alt_name
      if alt_name_ru and alt_name_ru!="":
        row['alt_name'] = alt_name_ru
      row['old_name'] = old_name
      row['official_name'] = official_name
      row['railway'] = railway
      row['type'] = "%d" % typi[typ]
      row['source'] = source
      # FIXME
      row['user'] = esr_user
      k = "%d-%d" % (typi[typ],osm_id)
      for kk in row.keys():
        if not row[kk]:
          row[kk] = ''
        else:
          row[kk] = str(row[kk])
      if k in osmdata.keys():
        dirty = 0
        for kk in row.keys():
          if kk == 'st' or kk == 'id':
            continue
          if (row[kk] or osmdata[k][kk]) and str(osmdata[k][kk]) != str(row[kk]):
            osmdata[k][kk] = row[kk]
            dirty = 1
            print "FIXME ...... %s [%s] | [%s]" % (kk,row[kk],osmdata[k][kk])
            print "FIXME types: " + str(typeof(row[kk])) + " " + str(typeof(osmdata[k][kk]))
        osmdata[k]['st'] = dirty
      else:
        print "NEW ROW %s" % k
        row['st'] = 2
        osmdata[k] = row
else:
  f = open(input)
  a = osmParser(f, source)
  
print "OSMDATA: %d records after parse" % len(osmdata)

# ===
#  Stage 3. Update osmdata
# ===
ks = osmdata.keys()
for k in ks:
  row = osmdata[k]
  if row["st"] == -1:
    print "%s to be deleted" % k
    del osmdata[k]
    db.query("DELETE FROM osmdata WHERE id=" + str(row['id']))
    db.query("DELETE FROM osm2esr WHERE osmdata_id=" + str(row['id']))
  elif row["st"] == 0:
    #print "%s found, but unchanged" % k
    True
  elif row["st"] == 1:
    print "%s found and changed" % k
    id = row["id"]
    del row["id"]
    del row["st"]
    q = []
    for kk in row.keys():
      q.append(kk+"='"+db.escape_string(str(row[kk]))+"'")
    q = "UPDATE osmdata SET "+(",".join(q))+" WHERE id="+str(id)
    db.query(q)
    db.query("DELETE FROM osm2esr WHERE osmdata_id=" + str(id))
    osmdata[k]['id'] = id
  elif row["st"] == 2:
    print "%s is new" % k
    del row["st"]
    q="INSERT INTO osmdata ("+(",".join(row.keys()))+") VALUES ('"+("','".join(map(db.escape_string,row.values())))+"')"
    try:
      db.query(q)
    except _mysql_exceptions.IntegrityError:
      print "DUPLICATE KEY %s" % k
      print "CANNOT FIX THIS AUTOMATICALLY!"
      sys.exit(1)
    c.execute("SELECT LAST_INSERT_ID() AS id")
    id = c.fetchone()
    id = id["id"]
    osmdata[k]["id"]=id
  else:
    print "UNKNOWN st VALUE!"
    sys.exit(1)

# ===
#  Stage 4. OSM<=>ESR
# ===

q = "SELECT id FROM regions WHERE source='%s'" % source
c.execute(q)
regions = []
while 1:
  row = c.fetchone()
  if not row:
    break
  regions.append(str(row["id"]))
regions = ",".join(regions)

stations = {}
q = "SELECT * FROM stations WHERE region_id IN (%s) AND dup_esr=''" % regions
c.execute(q)
while 1:
  row = c.fetchone()
  if not row:
    break
  stations[row['esr']] = row
print "%d stations loaded" % len(stations)

osm2esr = {}
q = "SELECT osm2esr.id AS id,esr,osmdata_id,status FROM osm2esr,osmdata WHERE osmdata_id=osmdata.id AND osmdata.source='%s'" % source
c.execute(q)
while 1:
  row = c.fetchone()
  if not row:
    break
  row['st'] = -1
  osm2esr["%s-%s" % (row["esr"],row["osmdata_id"])] = row
print "%d osm2esr records loaded" % len(osm2esr)

osm2esrs = {}
osm2user = {}
q_esrnf = 0
for k in osmdata.keys():
  name = osmdata[k]['name'].decode("utf-8")
  alt_name = osmdata[k]['alt_name'].decode("utf-8")
  old_name = osmdata[k]['old_name'].decode("utf-8")
  official_name = osmdata[k]['official_name'].decode("utf-8")
  user = osmdata[k]['user']
  osmdata_id = str(osmdata[k]['id'])
  try:
    if user:
      int(user)
  except:
    user = ''
    pass
  if user and user=='0':
    continue
  if user and user!='':
    try:
      st = stations[user]
    except KeyError:
      st = None
    if st:
      osm2user[osmdata_id] = user
      print "FOUND USER name=%s esr:user=%s esr_name=%s" % (name, user, st['name'])
      try:
        osm2esr["%s-%s" % (user, osmdata_id)]['st'] = 0
      except:
        db.query("INSERT INTO osm2esr (osmdata_id,esr) VALUES ('%s','%s')" % (osmdata_id, user))
        pass
      osm2esrs[osmdata_id]=[user]
    else:
      # don't touch esr2osm[user]['st'] here!
      print "WARNING esr:user=%s NOT FOUND IN ESR" % user
    continue
  dirty = 0
  for esr in stations.keys():
    st = stations[esr]
    cn = cname_esr(st['name'])
    if cn == cname_osm(name) or cn == cname_osm(alt_name) or cn == cname_osm(old_name) or cn == cname_osm(official_name):
      print "FOUND name=%s alt_name=%s old_name=%s official_name=%s esr_name=%s esr=%s" % (name,alt_name,old_name,official_name,st['name'],esr)
      try:
        osm2esr["%s-%s" % (esr, osmdata_id)]['st'] = 0
      except:
        db.query("INSERT INTO osm2esr (osmdata_id,esr) VALUES ('%s','%s')" % (osmdata_id, esr))
        pass
      try:
        osm2esrs[osmdata_id].append(esr)
      except:
        osm2esrs[osmdata_id]=[esr]
      dirty = dirty + 1
  if not dirty:
    print "NOT FOUND name=%s" % name
    q_esrnf += 1
  if dirty > 1:
    print "MORE THAN ONE name=%s" % name

# ===
#  Stage 5. Update osm2esr data
# ===

for k in osm2esr.keys():
  oe = osm2esr[k]
  if oe['st']<0:
    print "DELETE OLD esr-osm %s-%s" % (oe['esr'], oe['osmdata_id'])
    db.query("DELETE FROM osm2esr WHERE esr='%s' AND osmdata_id='%s'" % (oe['esr'], oe['osmdata_id']))

osm2esr = None
esr2osm = dict()

q_found = 0
q_uniq = 0
q_nonuniq = 0

for osmdata_id in osm2esrs.keys():
  status = len(osm2esrs[osmdata_id])
  if status > 1:
    q_nonuniq += status
    status = 2
  else:
    esr2osm[str(osm2esrs[osmdata_id])] = 1
    q_uniq += 1
  #print "SET STATUS osmdata_id=%s status=%d" % (osmdata_id, status)
  db.query("UPDATE osm2esr SET status=%d WHERE osmdata_id='%s'" % (status, osmdata_id))
  try:
    user = osm2user[osmdata_id]
  except:
    continue
  if user:
    db.query("UPDATE osm2esr SET status=1 WHERE osmdata_id='%s' AND esr='%s'" % (osmdata_id, user))
q_found = len(esr2osm)

# ===
#  Stage 6. Finalize
# ===

print "%s: q_stations=%d q_found=%d q_uniq=%d q_nonuniq=%d q_esrnf=%d" % (source, len(stations), q_found, q_uniq, q_nonuniq, q_esrnf)
db.query("UPDATE regions SET q_stations=%d,q_found=%d,q_uniq=%d,q_nonuniq=%d,q_esrnf=%d,updated=%d WHERE source='%s'" % (len(stations),q_found,q_uniq,q_nonuniq,q_esrnf,int(time()),source))
