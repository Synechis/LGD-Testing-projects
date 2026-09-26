# Developing

## Tests

Kernel tests, in the groups `media_assist`, `media_assist_crop` and
`media_assist_embed`. They need the crop and focal_point modules in the
codebase. `composer.json` requires crop and lists focal_point under
`require-dev`, which is what the drupal.org GitLab CI installs for the test
job; a `test_dependencies` key in the `.info.yml` is not read there.

    php vendor/bin/phpunit -c web/core/phpunit.xml.dist web/modules/contrib/media_assist

The test classes carry `#[Group]` attributes and `@group` annotations, so the
groups are visible to PHPUnit 9 on Drupal 10 and PHPUnit 11 on Drupal 11.

## Code standards

    vendor/bin/phpcs --standard=Drupal,DrupalPractice --extensions=php,module,inc,install,profile,theme,yml web/modules/contrib/media_assist
    vendor/bin/phpstan analyse -c phpstan.neon web/modules/contrib/media_assist
    npx eslint --config web/core/.eslintrc.json web/modules/contrib/media_assist

`phpstan-baseline.neon` ignores the attribute classes that exist on only one
of the supported cores. `.prettierrc.json` is a copy of core's, so the
JavaScript formats the same way inside and outside a Drupal tree.

## These docs

The pages are Markdown files in `docs/`, listed in `mkdocs.yml`. To build
them, install [MkDocs](https://www.mkdocs.org/) and run it from the project
root:

    pip install mkdocs
    mkdocs serve

No theme or plugin beyond MkDocs itself is needed.

On drupal.org, the GitLab CI template builds the same pages with the same
`mkdocs.yml` and publishes them to GitLab Pages on every commit to the
default branch. The build is strict: a link to a page that does not exist
fails it.
