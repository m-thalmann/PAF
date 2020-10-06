# PAF

PAF (PHP API Framework) is a framework for creating API's through PHP and outputting them as JSON (also other formats are possible).

This file explains the main concepts and functions of PAF. If you want a more in-depth documentation, you find the phpDocumentator documentation of all classes here: https://m-thalmann.github.io/PAF

## Table of contents

-   [Setting up](#setting-up)
-   [Quick start](#quick-start)
-   [Components](#components)
-   [Contributing](#contributing)

## Setting up

1. Download this repository and copy the contents of the `src/` folder into (for example) your `lib/PAF` folder
2. Create a `index.php` file and require the `lib/PAF/autload.php` file. This will automatically load the needed classes. This will **not** interfere with your own/other autoloaders!
    - **Alternatively:** Require each file you need separately
3. If you want to use the `PAF\Router`, you should also follow step 2 of its setup-guide
4. Now you are ready to go

<hr>

## Quick start

```php
<?php
require_once 'path/to/autoload.php';

// start using PAF
use PAF\Router\Router;

Router::init();

// ...

?>
```

<hr>

## Components

PAF contains different components for different use-cases:

-   `PAF\Router` - Contains classes for routing and outputting responses (mainly) as json ([README](src/Router/README.md))

## Contributing

### Prettier

When contributing please run prettier before commiting to the repository:

1.  Install prettier (with php-plugin): `npm install --global prettier @prettier/plugin-php`
2.  Run prettier: `prettier --write .`
