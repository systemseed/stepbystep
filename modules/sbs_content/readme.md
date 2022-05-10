SBS default content
===================

Introduction
------------

The module holds default content for Step by Step distribution. The content stored in serialized format which can be imported via [Default Content](https://www.drupal.org/project/default_content) module.

Requirements
------------

This module requires the following modules:

 * [Default Content](https://www.drupal.org/project/default_content)

Usage
-----

The content will be imported from module to the site automatically on module
enabling.

Exporting content
-----------------

!!!IMPORTANT!!! Never use on production site!

The process will make irreversible changes to the content, so it should be
performed on test environment.

To update default content in future, please follow next steps.

1. Update UUIDs lists per entity type.

    For getting UUIDs you can use following commands:

    ```
    drush sqlq 'SELECT uuid FROM node'
    drush sqlq 'SELECT uuid FROM config_pages'
    drush sqlq 'SELECT uuid FROM menu_link_content'
    drush sqlq 'SELECT uuid FROM taxonomy_term_data'
    drush sqlq 'SELECT uuid FROM file_managed'
    ```

    Note: paragraphs, translations, path aliases includes to parent entities,
    no needs to export them separately.

2. Run script for change uid for nodes and files.

    ```
    drush php:eval '_reasign_default_content_to_admin()'
    ```

3. Run command for export content.

    ```
    drush dcem sbs_content
    ```
