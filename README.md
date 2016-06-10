# Fallback Formatter

[![Travis build status](https://img.shields.io/travis/drupal-media/fallback_formatter/8.x-1.x.svg)](https://travis-ci.org/drupal-media/fallback_formatter) [![Scrutinizer code quality](https://img.shields.io/scrutinizer/g/drupal-media/fallback_formatter/8.x-1.x.svg)](https://scrutinizer-ci.com/g/drupal-media/fallback_formatter)
[Fallback Formatter](https://www.drupal.org/project/fallback_formatter) provides
a field formatter that can attempt multiple formatters and the first one that
returns output wins.

## Installation

1. Download 
   [Fallback Formatter](https://www.drupal.org/project/fallback_formatter) from
   [Drupal.org](https://www.drupal.org/project/fallback_formatter/releases).
2. Install it in the 
   [usual way](https://www.drupal.org/documentation/install/modules-themes/modules-8).

## Usage

Setup field formatter:
  * On `admin/structure/types` choose the content type you want to use for
    fallback formatter, for example *Article*. 
  * Select **Manage Display** and go to 
    `admin/structure/types/manage/article/display`.
  * Choose a field you want to use and in formatter settings define **Fallback**
    as the format.
  * In formatter settings under *Enabled formatters* check the formatters you
    want to use.
  * Under *Formatter processing weight* you can order the formatters, the first
    one that returns some result will be used.
  * Bellow the *Formatter processing weight* you can define settings for each
    enabled formatter if available.
