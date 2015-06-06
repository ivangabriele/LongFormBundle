# IBLongFormBundle

This bundle provides an easy way to automatically generate form classes from a simple Yaml file (which represents the form model) via the CLI. Here are the classes that can be automatically generated :

* the Symfony Form Type class,
* the Twig template (to be included or rendered in your own templates),
* the Doctrine ORM Entity class.

[![Build Status](https://travis-ci.org/InspiredBeings/IBLongFormBundle.svg?branch=master)](https://travis-ci.org/InspiredBeings/IBLongFormBundle)
[![Code Coverage](https://scrutinizer-ci.com/g/InspiredBeings/IBLongFormBundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/InspiredBeings/IBLongFormBundle/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/InspiredBeings/IBLongFormBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/InspiredBeings/IBLongFormBundle/?branch=master)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/ed43e7c8-502f-47ae-96db-18d295b573b8/big.png)](https://insight.sensiolabs.com/projects/ed43e7c8-502f-47ae-96db-18d295b573b8)

---

## Installation

### Install IBLongFormBundle

Add the following dependency to your *composer.json* file :

**composer.json**

``` json
{
    "require": {
        "inspired-beings/long-form-bundle": "1.*"
    }
}
```

### Enable the bundle

And enable the bundle in the kernel :

**app/AppKernel.php**

``` php
<?php

public function registerBundles()
{
    $bundles = array(
        // ...
        new InspiredBeings\LongFormBundle\IBLongFormBundle(),
    );
}
```

## How to use it ?

### File structure

Here is the file structure that IBLongFormBundle is based upon. Also, naming conventions are self-explanatory.

```
.
├── src
    └── MyCompany
        └── MyBrand
            └── MyBundle
                ├── Entity
                |   └── StudentApplication.php (generated)
                ├── Form
                |   ├── Model
                |   |   └── StudentApplication.yml (form model to write)
                |   └── Type
                |       └── StudentApplicationType.php (generated)
                └── Ressources
                    └── views
                        └── Form
                            └── student_application.html.twig (generated)
```

### Writing the Form Model

The form model is a simple Yaml file based upon [Symfony Form Types Reference](http://symfony.com/doc/current/reference/forms/types.html) :

> **Important**
> 
> * Be careful not to use **defaults** keyword as a field name since it is used to set the general options for the Form Type.
> * By default, the generated form field type is a **string**.

**Form/Model/StudentApplication.yml**

``` yml
# -------------------------------------------
# Form default options
# @see http://symfony.com/doc/current/reference/forms/types/form.html

defaults:
    required: false

# -------------------------------------------
# Form fields
# @see http://symfony.com/doc/current/reference/forms/types.html
# 
studentCivility:
    type: choice
    label: "Civility"
    required: true
    choices:
        'Mr': 'Mister'
        'Ms': 'Miss'
    expanded: true
studentFirstName:
    label: "First Name"
    required: true
studentLastName:
    label: "Last Name"
    required: true
studentBirthDate:
    label: "Birth Date"
    type: birthday
studentAddress:
    label: "Adress 1"
studentAddressBis:
    label: "Adress 2"
studentZipCode:
    label: "Zip Code"
studentCity:
    label: "City"
studentCountry:
    type: country
    label: "Country"
    data: "US"
studentEmail:
    type: email
    label: "Email"
    required: true
studentPhone:
    label: "Phone"

# -------------------------------------------
# Form buttons
# @see http://symfony.com/doc/current/reference/forms/types.html#buttons
# 
reset:
    label: "RESET"
    type: reset
    attr: { class: 'button-reset' }
save:
    label: "SEND"
    type: submit
    attr: { class: 'button-submit' }
```

### Generating the files

Open your favorite CLI and go into your Symfony project :

    php app/console generate:form MyBundleName:StudentApplication

This will generate all the files marked as *(generated)* in the file structure above :

* `Entity/StudentApplication.php`
* `Form/Type/StudentApplicationType.php`
* `Ressources/views/Form/student_application.html.twig`

If you **don't want to generate the Doctrine Entity**, you can add the option `--no-entity` :

    php app/console generate:form MyBundleName:StudentApplication --no-entity

And if you want to **automatically update your database** (calling the `doctrine:schema:update --force`), you can add the option `--schema-update` :

    php app/console generate:form MyBundleName:StudentApplication --schema-update
