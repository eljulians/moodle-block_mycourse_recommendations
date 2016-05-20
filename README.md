MyCourse Recommendations block
==============================

![Release](https://img.shields.io/badge/release-v1.2-blue.svg) ![Supported](https://img.shields.io/badge/supported-Moodle%202.9,%20Moodle%203.0-green.svg)

This plugin generates custom recommendations for the students of a course, based on previous students preferences on the same content.

## Current version
The latest release is the v1.2 (build 2016052000) for Moodle 2.9 and 3.0. Checkout [v2.9.1.2](https://github.com/julenpardo/moodle-block_mycourse_recommendations/releases/tag/v2.9.1.2) and [v3.0.1.2](https://github.com/julenpardo/moodle-block_mycourse_recommendations/releases/tag/v3.0.1.2) releases, respectively.

## How does it work?
In the creation of recommendations process, this system performs several operations.

The first one is the data gathering; data of previous teachings of courses, including the resources seen by the students grouped in weeks, and the final grade obtained by each one. This data is used for determining in which way do the resources influence in the degree of success of the students, for generating patterns of recommendable resources to pay attention at through the time.
This data can be, currently, gathered from two different sources:

 - Moodle core: data of previous teachings in the same instance the plugin is installed.
 - External source: if there's no existing data in the same Moodle instance, this can be manually imported to the plugin to populate its tables, in CSV format.

Once the system can be provided of this historic data, the next step is to associate the students of the course that is being currently taught, with the historic users. To make this associations, the [cosine similarity](https://en.wikipedia.org/wiki/Cosine_similarity) method is used, a measure of similarity between vectors. These vectors are the views that each user generates for each resource. Every current user is compared to every historic user using this method, and the highest similarity coefficient will determine which historic user is the most similar to each current user.

Having the current user an historic user associated, the recommendations can be generated. To generate them, the recommendator looks at the most viewed resources of the associated historic user, to later see if those resources also exist in the current course teaching. Every existing resource in both teachings will be saved as recommendable. These resources are ranked, setting a priority for each one, based on the total number of views by the historic user.

## Limitations
This version is only tested for PostgreSQL DBMS.

## Changes from v1.1
 - Fix issue "`database_helper::has_data_to_be_imported()` does not do the proper operations to determine if actually the data has to be imported" (see [issue 50](https://github.com/julenpardo/moodle-block_mycourse_recommendations/issues/50)).
 - Add more info to the logs (see [issue 52](https://github.com/julenpardo/moodle-block_mycourse_recommendations/issues/52)).

## Upcoming features and enhancements
 - Database agnostic.
 - Settings for the customization of the number of recommendations to display, the filter for the determination of "customizability" of the courses, etc.
 - Performance optimization.

## Installation & Usage
 - Copy content to /path/to/moodle/blocks/mycourse_recommendations.
 - Install it from Moodle.
 - Go to the course where you want to enable the plugin, and select *Turn editing on*.
 - Find the *Add a block* block, and find in the list *MYCOURSE recomendations* block.
 - By default, the recommendations will be generated at Monday 01:00 am. So, at first, no recommendations will be displayed (if the course passes the filter to consider it customisable, of course).

## Release naming
The releases are tagged the following way:  *vX.Y.Z.W*, where:

 - *X.Y* is Moodle version, in *mayor.minor* format.
 - *Z.W* is plugin version, in *mayor.minor* format.

## Extras
The *util/* folder contains the SQLs for querying the data to generate the CSV for the importation.

## Acknowledgements
 - Iñaki Arenaza
 - Rosa Basagoiti
 - Iñigo Zendegi
