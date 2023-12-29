create table if not exists medals
(
	idMedal bigint unsigned not null
		primary key,
	idUnit bigint unsigned not null,
	weight smallint default 0 not null,
	name varchar(64) not null,
	image text null,
	dateCreated datetime default CURRENT_TIMESTAMP not null,
	dateUpdated datetime default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP
);

create table if not exists members_endorsements
(
	idMemberEndorsement int unsigned auto_increment
		primary key,
	idMember bigint unsigned null,
	idEndorsement bigint unsigned null,
	dateCreated datetime null,
	dateUpdated datetime null
);

create table if not exists members_medals
(
	idMemberMedal int unsigned auto_increment
		primary key,
	idMember bigint unsigned not null,
	idMedal bigint unsigned not null,
	remarks text null,
	dateAwarded datetime not null,
	dateCreated datetime default CURRENT_TIMESTAMP not null,
	dateUpdated datetime default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP
);

create table if not exists operations
(
	idOp bigint unsigned not null
		primary key,
	idUnit bigint unsigned not null,
	dateOp datetime not null,
	name varchar(64) default '' not null,
	remarks text null,
	remarksInternal text null,
	dateCreated datetime default CURRENT_TIMESTAMP not null,
	dateUpdated datetime default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP
);

create table if not exists ranks
(
	idRank bigint unsigned not null
		primary key,
	idUnit bigint unsigned not null,
	weight smallint default 0 not null,
	abbr varchar(16) not null,
	name varchar(64) not null,
	icon text null,
	dateCreated datetime default CURRENT_TIMESTAMP not null,
	dateUpdated datetime default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP
);

create table if not exists sessions
(
	idSession varchar(64) not null
		primary key,
	data text not null,
	ip binary(16) null,
	userAgent varchar(255) null,
	idUser bigint unsigned null,
	dateCreated datetime default CURRENT_TIMESTAMP not null,
	dateUpdated datetime default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP
);

create index sessions_idUser_index
	on sessions (idUser);

create table if not exists squads
(
	idGroup bigint unsigned not null
		primary key,
	idUnit bigint unsigned not null,
	idParent bigint unsigned null,
	weight smallint default 0 not null,
	name varchar(64) not null,
	color char(7) null,
	dateCreated datetime default CURRENT_TIMESTAMP not null,
	dateUpdated datetime default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP
);

create table if not exists units
(
	idUnit bigint unsigned not null
		primary key,
	name varchar(64) not null,
	slug varchar(32) null,
	icon mediumtext null,
	dateCreated datetime default CURRENT_TIMESTAMP not null,
	dateUpdated datetime default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP,
	constraint slug
		unique (slug)
);

create table if not exists endorsements
(
	idEndorsement bigint unsigned not null
		primary key,
	idUnit bigint unsigned null,
	weight smallint null,
	abbr varchar(16) null,
	name varchar(64) null,
	dateCreated datetime null,
	dateUpdated datetime null,
	constraint endorsements_units_idUnit_fk
		foreign key (idUnit) references units (idUnit)
			on delete cascade
);

create table if not exists members
(
	idMember bigint unsigned not null
		primary key,
	idUnit bigint unsigned not null,
	idRank bigint unsigned not null,
	idGroup bigint unsigned null,
	name varchar(64) not null,
	playerName varchar(64) default '' not null,
	role varchar(64) default '' not null,
	dateJoined date null,
	dateLastPromotion date null,
	remarks text null,
	remarksInternal text null,
	dateCreated datetime default CURRENT_TIMESTAMP not null,
	dateUpdated datetime default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP,
	constraint members_ranks_idRank_fk
		foreign key (idRank) references ranks (idRank),
	constraint members_squads_idGroup_fk
		foreign key (idGroup) references squads (idGroup)
			on delete set null,
	constraint members_units_idUnit_fk
		foreign key (idUnit) references units (idUnit)
			on delete cascade
);

create table if not exists units_editors
(
	idUnitEditor int unsigned auto_increment
		primary key,
	idUnit bigint unsigned not null,
	idUser bigint unsigned not null,
	dateCreated datetime default CURRENT_TIMESTAMP not null,
	dateUpdated datetime default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP
);

create table if not exists users
(
	idUser bigint unsigned not null
		primary key,
	username varchar(64) not null,
	displayName varchar(64) null,
	avatar varchar(64) null,
	banner varchar(64) null,
	dateCreated datetime default CURRENT_TIMESTAMP not null,
	dateUpdated datetime default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP,
	isAdmin tinyint(1) default 0 not null,
	isMod tinyint(1) default 0 not null,
	isBanned tinyint(1) default 0 not null,
	constraint username_UNIQUE
		unique (username)
);


