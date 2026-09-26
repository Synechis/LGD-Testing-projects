# LocalGovDrupal Services

Provides the pages and navigation for presenting the Services provided by
Local Government. A part of the LocalGovDrupal distribution.

Content types:

* Landing page - the top level section for each service.
* Sub-landing page - detail and links to specific pages within a service.
* Page - the basic page that can be placed in a service, and on a service
  sub-landing page.
* Status - an optional additional type for providing updates about a the status
  of a service.

Other content types in the LocalGovDrupal distribution can also optionally
be linked into service sections and referenced from sub-landing pages.

# Failing dragTo tests

We have removed some phpunit tests that tested the draggable element provided
by the localgov_services_navigation module. The tests were failing randomly in
Drupal 11, but the functionality was working fine for manual testing. We would
like to bring back some testing of this drag and drop functionality in due
course. The original tests can be seen at the commit below.

https://git.drupalcode.org/project/localgov_services/-/blob/7cc551286d874234b987f3419346bd136274b9b8/modules/localgov_services_navigation/tests/src/FunctionalJavascript/LandingPageChildrenTest.php#L141