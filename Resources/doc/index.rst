DoctrineMigrationsBundle
========================

The database migrations feature is an extension of the database abstraction
layer and offers you the ability to programmatically deploy new versions of
your database schema in a safe, easy and standardized way.

.. tip::

    You can read more about the Doctrine Database Migrations on the project's
    `documentation`_.

Installation
------------

Doctrine migrations for Symfony are maintained in the `DoctrineMigrationsBundle`_.
The bundle uses external `Doctrine Database Migrations`_ library.

Follow these steps to install the bundle and the library in the Symfony
Standard edition. Add the following to your ``composer.json`` file:

.. code-block:: json

    {
        "require": {
            "doctrine/doctrine-migrations-bundle": "dev-master"
        }
    }

Update the vendor libraries:

.. code-block:: bash

    $ php composer.phar update

If everything worked, the ``DoctrineMigrationsBundle`` can now be found
at ``vendor/doctrine/doctrine-migrations-bundle``.

.. note::

    ``DoctrineMigrationsBundle`` installs
    `Doctrine Database Migrations`_ library. The library can be found
    at ``vendor/doctrine/migrations``.

Finally, be sure to enable the bundle in ``AppKernel.php`` by including the
following:

.. code-block:: php

    // app/AppKernel.php
    public function registerBundles()
    {
        $bundles = array(
            //...
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
        );
    }

Usage
-----

All of the migrations functionality is contained in a few console commands:

.. code-block:: bash

    doctrine:migrations
      :diff     Generate a migration by comparing your current database to your mapping information.
      :execute  Execute a single migration version up or down manually.
      :generate Generate a blank migration class.
      :migrate  Execute a migration to a specified version or the latest available version.
      :status   View the status of a set of migrations.
      :version  Manually add and delete migration versions from the version table.

Start by getting the status of migrations in your application by running
the ``status`` command:

.. code-block:: bash

    php app/console doctrine:migrations:status

     == Configuration

        >> Name:                                               Application Migrations
        >> Configuration Source:                               manually configured
        >> Version Table Name:                                 migration_versions
        >> Migrations Namespace:                               Application\Migrations
        >> Migrations Directory:                               /path/to/project/app/DoctrineMigrations
        >> Current Version:                                    0
        >> Latest Version:                                     0
        >> Executed Migrations:                                0
        >> Available Migrations:                               0
        >> New Migrations:                                     0

Now, you can start working with migrations by generating a new blank migration
class. Later, you'll learn how Doctrine can generate migrations automatically
for you.

.. code-block:: bash

    php app/console doctrine:migrations:generate
    Generated new migration class to "/path/to/project/app/DoctrineMigrations/Version20100621140655.php"

Have a look at the newly generated migration class and you will see something
like the following::

    namespace Application\Migrations;

    use Doctrine\DBAL\Migrations\AbstractMigration,
        Doctrine\DBAL\Schema\Schema;

    class Version20100621140655 extends AbstractMigration
    {
        public function up(Schema $schema)
        {

        }

        public function down(Schema $schema)
        {

        }
    }

If you run the ``status`` command it will now show that you have one new
migration to execute:

.. code-block:: bash

    php app/console doctrine:migrations:status

     == Configuration

       >> Name:                                               Application Migrations
       >> Configuration Source:                               manually configured
       >> Version Table Name:                                 migration_versions
       >> Migrations Namespace:                               Application\Migrations
       >> Migrations Directory:                               /path/to/project/app/DoctrineMigrations
       >> Current Version:                                    0
       >> Latest Version:                                     2010-06-21 14:06:55 (20100621140655)
       >> Executed Migrations:                                0
       >> Available Migrations:                               1
       >> New Migrations:                                     1

    == Migration Versions

       >> 2010-06-21 14:06:55 (20100621140655)                not migrated

Now you can add some migration code to the ``up()`` and ``down()`` methods and
finally migrate when you're ready:

.. code-block:: bash

    php app/console doctrine:migrations:migrate

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

        // src/Acme/HelloBundle/Entity/User.php
        namespace Acme\HelloBundle\Entity;

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
            protected $id;

            /**
             * @ORM\Column(type="string", length="255")
             */
            protected $name;
        }

    .. code-block:: yaml

        # src/Acme/HelloBundle/Resources/config/doctrine/User.orm.yml
        Acme\HelloBundle\Entity\User:
            type: entity
            table: hello_user
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

        <!-- src/Acme/HelloBundle/Resources/config/doctrine/User.orm.xml -->
        <doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                            http://doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

            <entity name="Acme\HelloBundle\Entity\User" table="hello_user">
                <id name="id" type="integer" column="id">
                    <generator strategy="AUTO"/>
                </id>
                <field name="name" column="name" type="string" length="255" />
            </entity>

        </doctrine-mapping>

With this information, Doctrine is now ready to help you persist your new
``User`` object to and from the ``hello_user`` table. Of course, this table
doesn't exist yet! Generate a new migration for this table automatically by
running the following command:

.. code-block:: bash

    php app/console doctrine:migrations:diff

You should see a message that a new migration class was generated based on
the schema differences. If you open this file, you'll find that it has the
SQL code needed to create the ``hello_user`` table. Next, run the migration
to add the table to your database:

.. code-block:: bash

    php app/console doctrine:migrations:migrate

The moral of the story is this: after each change you make to your Doctrine
mapping information, run the ``doctrine:migrations:diff`` command to automatically
generate your migration classes.

If you do this from the very beginning of your project (i.e. so that even
the first tables were loaded via a migration class), you'll always be able
to create a fresh database and run your migrations in order to get your database
schema fully up to date. In fact, this is an easy and dependable workflow
for your project.

.. _documentation: http://docs.doctrine-project.org/projects/doctrine-migrations/en/latest/index.html
.. _DoctrineMigrationsBundle: https://github.com/doctrine/DoctrineMigrationsBundle
.. _`Doctrine Database Migrations`: https://github.com/doctrine/migrations
