MyCourse Recommendations block
==============================

This plugin will generate, every week, custom recommendations of what contents would be interesting to pay attention at, based on historical information of previous years.

## Current version
The latest release is the v1.0 (build 2016020102) for Moodle 2.9 and 3.0. Checkout [v2.9.1.0](https://github.com/julenpardo/moodle-block_mycourse_recommendations/releases/tag/v2.9.1.0) and [v3.0.1.0](https://github.com/julenpardo/moodle-block_mycourse_recommendations/releases/tag/v3.0.1.0) releases, respectively.

## Limitations
This version is only tested for PostgreSQL DBMS.

## Upcoming features/enhancements
 - Database agnostic.
 - Allow learning from external data sources, in CSV format.

## Installation
 - Copy content to *< moodle installation>*/blocks/mycourse_recommendations.
 - Install it from Moodle.

## Usage
 - Go to the course where you want to enable the plugin, and select *Turn editing on*.
 - Find the *Add a block* block, and find in the list *MYCOURSE recomendations* block.
 - By default, the recommendations will be generated at Sunday 04:00 am. So, at first, no recommendations will be displayed (if the course passes the filter to consider it customisable, of course).

## Release naming
The releases are tagged the following way:  *vX.Y.Z.W*, where:
 - *X.Y* is Moodle version, in *mayor.minor* format.
 - *Z.W* is plugin version, in *mayor.minor* format.

## Documentation for developers
See *doc/index.html *.
