create table esr_bounds (
  osm_id bigint not null,
  iso3166 varchar(8) not null default '',
  way geometry
);

create table esr_data (
  osm_type int not null default 0,
  osm_id bigint not null,
  iso3166 varchar(8),
  tags hstore,
  geom geometry
);
