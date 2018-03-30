create table `users` (
    `id` int unsigned not null auto_increment primary key,
    `name` varchar(255) not null,
    `email` varchar(255) not null,
    `password` varchar(255) not null,
    `remember_token` varchar(100) null,
    `created_at` timestamp null,
    `updated_at` timestamp null
) default character set utf8mb4 collate utf8mb4_unicode_ci;

create table `abilities` (
    `id` int unsigned not null auto_increment primary key,
    `name` varchar(150) not null,
    `title` varchar(255) null,
    `entity_id` int unsigned null,
    `entity_type` varchar(150) null,
    `only_owned` tinyint(1) not null default '0',
    `scope` int null,
    `created_at` timestamp null,
    `updated_at` timestamp null
) default character set utf8mb4 collate utf8mb4_unicode_ci;

alter table `abilities`
    add index `abilities_scope_index`(`scope`);

create table `roles` (
    `id` int unsigned not null auto_increment primary key,
    `name` varchar(150) not null,
    `title` varchar(255) null,
    `level` int unsigned null,
    `scope` int null,
    `created_at` timestamp null,
    `updated_at` timestamp null
) default character set utf8mb4 collate utf8mb4_unicode_ci;

alter table `roles`
    add unique `roles_name_unique`(`name`, `scope`);

alter table `roles`
    add index `roles_scope_index`(`scope`);

create table `assigned_roles` (
    `role_id` int unsigned not null,
    `entity_id` int unsigned not null,
    `entity_type` varchar(150) not null,
    `scope` int null
) default character set utf8mb4 collate utf8mb4_unicode_ci;

alter table `assigned_roles`
    add index `assigned_roles_entity_index`(`entity_id`, `entity_type`, `scope`);

alter table `assigned_roles`
    add constraint `assigned_roles_role_id_foreign`
        foreign key (`role_id`)
        references `roles` (`id`)
        on delete cascade
        on update cascade;

alter table `assigned_roles`
    add index `assigned_roles_role_id_index`(`role_id`);

alter table `assigned_roles`
    add index `assigned_roles_scope_index`(`scope`);

create table `permissions` (
    `ability_id` int unsigned not null,
    `entity_id` int unsigned not null,
    `entity_type` varchar(150) not null,
    `forbidden` tinyint(1) not null default '0',
    `scope` int null
) default character set utf8mb4 collate utf8mb4_unicode_ci;

alter table `permissions`
    add index `permissions_entity_index`(`entity_id`, `entity_type`, `scope`);

alter table `permissions`
    add constraint `permissions_ability_id_foreign`
        foreign key (`ability_id`)
        references `abilities` (`id`)
        on delete cascade
        on update cascade;

alter table `permissions`
    add index `permissions_ability_id_index`(`ability_id`);

alter table `permissions`
    add index `permissions_scope_index`(`scope`);
