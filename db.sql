create table if not exists regions (
  id int primary key auto_increment,
  name varchar(255) not null default '',
  esr_name varchar(255) not null,
  source varchar(255) not null,
  iso3166 varchar(6) not null default '',
  country varchar(255) not null,
  q_stations int not null default 0,
  q_uniq int not null default 0,
  q_nonuniq int not null default 0,
  q_esrnf int not null default 0,
  updated int not null default 0
);

create table if not exists railways (
  id int primary key auto_increment,
  name varchar(255) not null default '',
  esr_name varchar(255) not null,
  map_url varchar(255) not null default '',
  code varchar(2) not null default '',
  abbr varchar(255) not null default '',
  url varchar(255) not null default '',
  pass_map_url varchar(255) not null default '',
  name_rp varchar(255) not null default ''
);

create table if not exists stations (
  id int primary key auto_increment,
  esr varchar(6) not null unique,
  short_name varchar(255) not null default '',
  name varchar(255) not null default '???',
  region_id int not null,
  railway_id int not null,
  division_id int not null,
  country_id int not null,
  name_rzd0 varchar(255) not null default '',
  name_tr4k1 varchar(255) not null default '',
  dup_esr varchar(6) not null default '',
  name_rwua varchar(255) not null default '',
  gdevagon_lat float not null default 0,
  gdevagon_lon float not null default 0,
  express_code varchar(7) not null default '',
  yarasp_id int not null default 0,
  name_yarasp varchar(255) not null default '',
  yarasp_addr varchar(255) not null default '',
  yarasp_lat float not null default 0,
  yarasp_lon float not null default 0,
  yarasp_ex varchar(7) not null default '',
  station_type_id int not null default 0,
  closed varchar(255) not null default '',
  fixed int not null default 0,
  comment text not null default '',
  key (yarasp_id),
  key (express_code)
);

create table if not exists divisions (
  id int primary key auto_increment,
  esr_name varchar(255) not null,
  railway_id int not null,
  name varchar(255) not null default '',
  map_url varchar(255) not null default '',
  division_type_id int not null default 0
);

create table if not exists division_types (
  id int not null primary key auto_increment,
  name varchar(255) not null default ''
);

create table if not exists countries (
  id int primary key auto_increment,
  esr_name varchar(255) not null
);

create table if not exists osmdata (
  id int primary key auto_increment,
  osm_id bigint not null default -1,
  type int not null default 0, -- 0 - node, 1 - way, 2 - relation
  lat float not null default 0,
  lon float not null default 0,
  name varchar(255) not null,
  -- name_ru varchar(255) not null,
  alt_name varchar(255) not null,
  -- alt_name_ru varchar(255) not null,
  old_name varchar(255) not null,
  official_name varchar(255) not null,
  -- station varchar(255) not null,
  railway varchar(255) not null,
  user varchar(255) not null,
  source varchar(255) not null,
  unique (type,osm_id)
);

create table if not exists osm2esr (
  id int primary key auto_increment,
  osmdata_id int not null,
  esr varchar(255) not null default '',
  status int not null default -1, -- 1 - found one, 2 - found many
  unique (esr,osmdata_id)
);

create table if not exists neighb_stations (
  id int primary key auto_increment,
  station_esr varchar(6) not null,
  neighb_esr varchar(6) not null,
  KEY `station_esr` (`station_esr`), 
  KEY `neighb_esr` (`neighb_esr`),
  unique(station_esr, neighb_esr)
);

create table if not exists station_types (
  id int primary key,
  name varchar(255) not null default ''
);

create table if not exists express (
  id int primary key auto_increment,
  express_code varchar(7) not null default '' unique,
  express_railway varchar(5) not null default '',
  name varchar(255) not null default '',
  tutu_lat float not null default 0,
  tutu_lon float not null default 0,
  is_city int not null default 0,
  alias varchar(255) not null default ''
);

create table if not exists `lines` (
  id int primary key auto_increment,
  name_tr4 varchar(255) not null default '',
  esr1 varchar(6) not null default '',
  esr2 varchar(6) not null default '',
  comment varchar(255) not null default '',
  sidx int not null default 0
);

create table if not exists stations_of_lines (
  id int primary key auto_increment,
  line_id int not null default 0,
  esr varchar(6) not null default '',
  sidx int not null default 0,
  key (esr)
);
