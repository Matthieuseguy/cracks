create table users(
id integer not null primary key autoincrement,
login varchar(200) not null unique,
pwd varchar(200) not null,
isadmin boolean not null default 0
isadmin boolean not null default 0,
failed_attempts INT DEFAULT 0,
locked_until DATETIME NULL
);

create table cracks(
id integer not null primary key autoincrement,
content text not null,
owner int not null,
datesend int not null
);

create table votes(
crack integer not null,
voter integer not null,
val integer not null
);

insert into users (login, pwd, isadmin, failed_attempts, locked_until)
values('admin', '$2y$10$KqN.xmpaFibaMr4sy5W/Je0m.CVF/HJ7Gg2UDsX8xNyeAaKki0hQG', 1, 0, NULL);