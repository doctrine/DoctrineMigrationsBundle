## Installation

### Get the bundle

To install the bundle, place it in the `vendor/bundles/Symfony` directory of your project
(so that it lives at `vendor/bundles/Symfony/Bundle/DoctrineMigrationsBundle`). You can do this by adding
the bundle as a submodule, cloning it, or simply downloading the source.

    git submodule add http://github.com/symfony/DoctrineMigrationsBundle.git vendor/bundles/Symfony/Bundle/DoctrineMigrationsBundle

### Change `Symfony` namespace in your autoloader

    // app/autoload.php
    $loader->registerNamespaces(array(
        'Symfony'                        => array(__DIR__.'/../vendor/symfony/src', __DIR__.'/../vendor/bundles'),
        // ...
    ));

### Initialize the bundle

To start using the bundle, initialize the bundle in your Kernel. This
file is usually located at `app/AppKernel`:

    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Symfony\Bundle\DoctrineMigrationsBundle\DoctrineMigrationsBundle(),
        );
    )

That's it!
