# Upgrade

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
            version_column_length: 1024
            executed_at_column_name: 'executed_at'
```
- The migration name has been dropped:

Before

```yaml
doctrine_migrations:
    name: 'Application Migrations'
```

After

The parameter `name` has been dropped.
