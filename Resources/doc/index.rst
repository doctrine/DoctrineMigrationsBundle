DoctrineMigrationsBundle
========================

Database migrations are a way to safely update your database schema both locally
and on production. Instead of running the ``doctrine:schema:update`` command or
applying the database changes manually with SQL statements, migrations allow to
replicate the changes in your database schema in a safe manner.

Migrations are available in Symfony applications via the `DoctrineMigrationsBundle`_,
which uses the external `Doctrine Database Migrations`_ library. Read the
`documentation`_ of that library if you need a general introduction about migrations.

Installation
------------

Run this command in your terminal:

.. code-block:: terminal

    $ composer require doctrine/doctrine-migrations-bundle "^3.0"

If you don't use `Symfony Flex`_, you must enable the bundle manually in the application:

.. code-block:: php

    // config/bundles.php
    // in older Symfony apps, enable the bundle in app/AppKernel.php
    return [
        // ...
        Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    ];

Configuration
-------------

If you use Symfony Flex, the ``doctrine_migrations.yaml`` config file is created
automatically. Otherwise, create the following file and configure it for your
application:

.. code-block:: yaml

    # config/packages/doctrine_migrations.yaml


    doctrine_migrations:
        # List of namespace/path pairs to search for migrations, at least one required
        migrations_paths:
            'App\Migrations': 'src/App'
            'AnotherApp\Migrations': '/path/to/other/migrations'
            'SomeBundle\Migrations': '@SomeBundle/Migrations'

        # List of additional migration classes to be loaded, optional
        migrations:
            - 'App\Migrations\Version123'
            - 'App\Migrations\Version123'

        # Connection to use for the migrations
        connection: default

        # Entity manager to use for migrations. This overrides the "connection" setting.
        em: default

        storage:
            # Default (SQL table) metadata storage configuration
            table_storage:
                table_name: 'doctrine_migration_versions'
                version_column_name: 'version'
                version_column_length: 1024
                executed_at_column_name: 'executed_at'

        # Possible values: "BY_YEAR", "BY_YEAR_AND_MONTH", false
        organize_migrations: false

        # Path to your custom migrations template
        custom_template: ~

        # Run all migrations in a transaction.
        all_or_nothing: false

        # Adds an extra check in the generated migrations to ensure that is executed on the same database type.
        check_database_platform: true

        services:
            # Custom migration sorting service id
            'Doctrine\Migrations\Version\Comparator': ~

            # Custom migration classes factory
            'Doctrine\Migrations\Version\MigrationFactory': ~

        factories:
            # Custom migration sorting service id via callables (MyCallableFactory must be a callable)
            'Doctrine\Migrations\Version\Comparator': 'MyCallableFactory'




- The ``services`` node allows you to provide custom services to the underlying ``DependencyFactory`` part of ``doctrine/migrations``.
- The node ``factories`` is similar to ``services``, with the difference that it accepts only callables.
The provided callable must return the service to be passed to the ``DependencyFactory``.
The callable will receive as first argument the ``DependencyFactory`` itself,
allowing you to fetch other dependencies from the factory while instantiating your custom dependencies.

Usage
-----

All of the migrations functionality is contained in a few console commands:

.. code-block:: terminal

    doctrine
     doctrine:migrations:current                [current] Outputs the current version.
     doctrine:migrations:diff                   [diff] Generate a migration by comparing your current database to your mapping information.
     doctrine:migrations:dump-schema            [dump-schema] Dump the schema for your database to a migration.
     doctrine:migrations:execute                [execute] Execute a single migration version up or down manually.
     doctrine:migrations:generate               [generate] Generate a blank migration class.
     doctrine:migrations:latest                 [latest] Outputs the latest version number
     doctrine:migrations:migrate                [migrate] Execute a migration to a specified version or the latest available version.
     doctrine:migrations:rollup                 [rollup] Roll migrations up by deleting all tracked versions and inserting the one version that exists.
     doctrine:migrations:status                 [status] View the status of a set of migrations.
     doctrine:migrations:up-to-date             [up-to-date] Tells you if your schema is up-to-date.
     doctrine:migrations:version                [version] Manually add and delete migration versions from the version table.
     doctrine:migrations:sync-metadata-storage  [sync-metadata-storage] Ensures that the metadata storage is at the latest version.
     doctrine:migrations:list                   [list-migrations] Display a list of all available migrations and their status.

Start by getting the status of migrations in your application by running
the ``status`` command:

.. code-block:: terminal

    $ php bin/console doctrine:migrations:status

This command will show you generic information about the migration status, such as how many migrations have been
already executed, which still need to run, and the database in use.

Now, you can start working with migrations by generating a new blank migration
class. Later, you'll learn how Doctrine can generate migrations automatically
for you.

.. code-block:: terminal

    $ php bin/console doctrine:migrations:generate

Have a look at the newly generated migration class and you will see something
like the following:

.. code-block:: php

    declare(strict_types=1);

    namespace DoctrineMigrations;

    use Doctrine\DBAL\Schema\Schema;
    use Doctrine\Migrations\AbstractMigration;

    /**
     * Auto-generated Migration: Please modify to your needs!
     */
    final class Version20180605025653 extends AbstractMigration
    {
        public function getDescription() : string
        {
            return '';
        }

        public function up(Schema $schema) : void
        {
            // this up() migration is auto-generated, please modify it to your needs

        }

        public function down(Schema $schema) : void
        {
            // this down() migration is auto-generated, please modify it to your needs

        }
    }

If you run the ``status`` command again it will now show that you have one new
migration to execute:

.. code-block:: terminal

    $ php bin/console doctrine:migrations:status --show-versions

Now you can add some migration code to the ``up()`` and ``down()`` methods and
finally migrate when you're ready:

.. code-block:: terminal

    $ php bin/console doctrine:migrations:migrate 'DoctrineMigrations\Version20180605025653'

For more information on how to write the migrations themselves (i.e. how to
fill in the ``up()`` and ``down()`` methods), see the official Doctrine Migrations
`documentation`_.

Running Migrations during Deployment
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Of course, the end goal of writing migrations is to be able to use them to
reliably update your database structure when you deploy your application.
By running the migrations locally (or on a beta server), you can ensure that
the migrations work as you expect.

When you do finally deploy your application, you just need to remember to run
the ``doctrine:migrations:migrate`` command. Internally, Doctrine creates
a ``migration_versions`` table inside your database and tracks which migrations
have been executed there. So, no matter how many migrations you've created
and executed locally, when you run the command during deployment, Doctrine
will know exactly which migrations it hasn't run yet by looking at the ``migration_versions``
table of your production database. Regardless of what server you're on, you
can always safely run this command to execute only the migrations that haven't
been run yet on *that* particular database.

Skipping Migrations
~~~~~~~~~~~~~~~~~~~

You can skip single migrations by explicitly adding them to the ``migration_versions`` table:

.. code-block:: terminal

    $ php bin/console doctrine:migrations:version 'App\Migrations\Version123' --add

.. tip::

    Pay attention to the single quotes (``'``) used in the command above, without them
    or with the double quotes (``"``) the command will not work properly.


Doctrine will then assume that this migration has already been run and will ignore it.

Migration Dependencies
----------------------

Migrations can have dependencies on external services (such as geolocation, mailer, data processing services...) that
can be used to have more powerful migrations. Those dependencies are not automatically injected into your migrations
but need to be injected using custom migrations factories.

Here is an example on how to inject the service container into your migrations:

.. configuration-block::

    .. code-block:: yaml

        # config/packages/doctrine_migrations.yaml
        doctrine_migrations:
            services:
                 'Doctrine\Migrations\Version\MigrationFactory': 'App\Migrations\Factory\MigrationFactoryDecorator'

        # config/services.yaml
        services:
            Doctrine\Migrations\Version\DbalMigrationFactory: ~
            App\Migrations\Factory\MigrationFactoryDecorator:
                decorates: Doctrine\Migrations\Version\DbalMigrationFactory
                arguments: ['@App\Migrations\Factory\MigrationFactoryDecorator.inner', '@service_container']


.. code-block:: php

    declare(strict_types=1);

    namespace App\Migrations\Factory;

    use Doctrine\Migrations\AbstractMigration;
    use Doctrine\Migrations\Version\MigrationFactory;
    use Symfony\Component\DependencyInjection\ContainerAwareInterface;
    use Symfony\Component\DependencyInjection\ContainerInterface;

    class MigrationFactoryDecorator implements MigrationFactory
    {
        private $migrationFactory;
        private $container;

        public function __construct(MigrationFactory $migrationFactory, ContainerInterface $container)
        {
            $this->migrationFactory = $migrationFactory;
            $this->container        = $container;
        }

        public function createVersion(string $migrationClassName): AbstractMigration
        {
            $instance = $this->migrationFactory->createVersion($migrationClassName);

            if ($instance instanceof ContainerAwareInterface) {
                $instance->setContainer($this->container);
            }

            return $instance;
        }
    }


Generating Migrations Automatically
-----------------------------------

In reality, you should rarely need to write migrations manually, as the migrations
library can generate migration classes automatically by comparing your Doctrine
mapping information (i.e. what your database *should* look like) with your
actual current database structure.

For example, suppose you create a new ``User`` entity and add mapping information
for Doctrine's ORM:

.. configuration-block::

    .. code-block:: php-annotations

        // src/Entity/User.php
        namespace App\Entity;

        use Doctrine\ORM\Mapping as ORM;

        /**
         * @ORM\Entity
         * @ORM\Table(name="hello_user")
         */
        class User
        {
            /**
             * @ORM\Id
             * @ORM\Column(type="integer")
             * @ORM\GeneratedValue(strategy="AUTO")
             */
            private $id;

            /**
             * @ORM\Column(type="string", length=255)
             */
            private $name;

    .. code-block:: yaml

        # config/doctrine/User.orm.yaml
        App\Entity\User:
            type: entity
            table: user
            id:
                id:
                    type: integer
                    generator:
                        strategy: AUTO
            fields:
                name:
                    type: string
                    length: 255

    .. code-block:: xml

        <!-- config/doctrine/User.orm.xml -->
        <doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                            http://doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

            <entity name="App\Entity\User" table="user">
                <id name="id" type="integer" column="id">
                    <generator strategy="AUTO"/>
                </id>
                <field name="name" column="name" type="string" length="255" />
            </entity>

        </doctrine-mapping>

With this information, Doctrine is now ready to help you persist your new
``User`` object to and from the ``user`` table. Of course, this table
doesn't exist yet! Generate a new migration for this table automatically by
running the following command:

.. code-block:: terminal

    $ php bin/console doctrine:migrations:diff

You should see a message that a new migration class was generated based on
the schema differences. If you open this file, you'll find that it has the
SQL code needed to create the ``user`` table. Next, run the migration
to add the table to your database:

.. code-block:: terminal

    $ php bin/console doctrine:migrations:migrate

The moral of the story is this: after each change you make to your Doctrine
mapping information, run the ``doctrine:migrations:diff`` command to automatically
generate your migration classes.

If you do this from the very beginning of your project (i.e. so that even
the first tables were loaded via a migration class), you'll always be able
to create a fresh database and run your migrations in order to get your database
schema fully up to date. In fact, this is an easy and dependable workflow
for your project.

If you don't want to use this workflow and instead create your schema via
``doctrine:schema:create``, you can tell Doctrine to skip all existing migrations:

.. code-block:: terminal

    $ php bin/console doctrine:migrations:version --add --all

Otherwise Doctrine will try to run all migrations, which probably will not work.

Manual Tables
-------------

It is a common use case, that in addition to your generated database structure
based on your doctrine entities you might need custom tables. By default such
tables will be removed by the ``doctrine:migrations:diff`` command.

If you follow a specific scheme you can configure doctrine/dbal to ignore those
tables. Let's say all custom tables will be prefixed by ``t_``. In this case you
just have to add the following configuration option to your doctrine configuration:

.. configuration-block::

    .. code-block:: yaml

        doctrine:
            dbal:
                schema_filter: ~^(?!t_)~

    .. code-block:: xml

        <doctrine:dbal schema-filter="~^(?!t_)~" />


    .. code-block:: php

        $container->loadFromExtension('doctrine', array(
            'dbal' => array(
                'schema_filter'  => '~^(?!t_)~',
                // ...
            ),
            // ...
        ));

This ignores the tables, and any named objects such as sequences, on the DBAL level and they will be ignored by the diff command.

Note that if you have multiple connections configured then the ``schema_filter`` configuration
will need to be placed per-connection.

.. _documentation: https://www.doctrine-project.org/projects/doctrine-migrations/en/current/index.html
.. _DoctrineMigrationsBundle: https://github.com/doctrine/DoctrineMigrationsBundle
.. _`Doctrine Database Migrations`: https://github.com/doctrine/migrations
.. _`Symfony Flex`: https://symfony.com/doc/current/setup/flex.html
