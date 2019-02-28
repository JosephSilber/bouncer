create table `abilities` (
    `id` int unsigned not null auto_increment primary key,
    `name` varchar(255) not null,
    `title` varchar(255) null,
    `entity_id` int unsigned null,
    `entity_type` varchar(255) null,
    `only_owned` tinyint(1) not null default '0',
    `options` json null,
    `scope` int null,
    `created_at` timestamp null,
    `updated_at` timestamp null
) default character set utf8mb4 collate utf8mb4_unicode_ci;

alter table `abilities`
    add index `abilities_scope_index`(`scope`);

create table `roles` (
    `id` int unsigned not null auto_increment primary key,
    `name` varchar(255) not null,
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
    `id` int unsigned not null auto_increment primary key,
    `role_id` int unsigned not null,
    `entity_id` int unsigned not null,
    `entity_type` varchar(255) not null,
    `restricted_to_id` int unsigned null,
    `restricted_to_type` varchar(255) null,
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
    `id` int unsigned not null auto_increment primary key,
    `ability_id` int unsigned not null,
    `entity_id` int unsigned null,
    `entity_type` varchar(255) null,
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
