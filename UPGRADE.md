# Upgrade

## Upgrade to 3.5

## Final classes

Some classes have been marked as `@final` because they are not supposed to be
extended. They will be `final`, and most of them will be marked with
`@internal` in 4.0.0.

## From 2.x to 3.0.0

- The configuration for the migration namespace and directory changed as follows:

Before

```yaml
doctrine_migrations:
    dir_name: '%kernel.project_dir%/src/Migrations'
    namespace: DoctrineMigrations
```

After

```yaml
doctrine_migrations:
    migrations_paths:
        'DoctrineMigrations': '%kernel.project_dir%/src/Migrations'
```

- The configuration for the metadata table definition changed as follows:

Before

```yaml
doctrine_migrations:
    table_name: 'migration_versions'
    column_name: 'version'
    column_length: 14
    executed_at_column_name: 'executed_at'
```

After

```yaml
doctrine_migrations:
    storage:
        table_storage:
            table_name: 'migration_versions'
            version_column_name: 'version'
            version_column_length: 191
            executed_at_column_name: 'executed_at'
```
If your project did not originally specify its own table definition configuration, you will need to configure the table name after the upgrade:

```yaml
doctrine_migrations:
    storage:
        table_storage:
            table_name: 'migration_versions'
```
and then run the `doctrine:migrations:sync-metadata-storage` command.
- The migration name has been dropped:

Before

```yaml
doctrine_migrations:
    name: 'Application Migrations'
```

After

The parameter `name` has been dropped.


- The default for `table_name` changed from `migration_versions` to `doctrine_migration_versions`. If you did not
specify the `table_name` option, you now need to declare it explicitly to not lose migration data.

```yaml
doctrine_migrations:
    storage:
        table_storage:
            table_name: 'migration_versions'
```

### Underlying doctrine/migrations library

Upgrading this bundle to `3.0` will also update the `doctrine/migrations` library to the version `3.0`.
Backward incompatible changes in `doctrine/migrations` 3.0
are documented in the dedicated [UPGRADE](https://github.com/doctrine/migrations/blob/3.0.x/UPGRADE.md) document.

- The container is not automatically injected anymore when a migration implements `ContainerAwareInterface`. Custom
migration factories should be used to inject additional dependencies into migrations.
